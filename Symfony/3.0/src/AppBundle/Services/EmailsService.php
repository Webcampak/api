<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Filesystem\Filesystem;

use \DateTime;

class EmailsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger,  SourcesService $sourceService, $paramEmailFrom, $dirSources, $dirEmails) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
        $this->sourceService   = $sourceService;
        $this->paramEmailFrom  = $paramEmailFrom;
        $this->dirSources      = $dirSources;
        $this->dirEmails       = $dirEmails;
    }

    public function validateDomain($emailAddress) {
        $this->logger->info('AppBundle\Services\EmailsService\validateDomain() - Start');
        $this->logger->info('AppBundle\Services\EmailsService\validateDomain() - Validate Email address: ' . $emailAddress);
        $domain = substr($emailAddress, strpos($emailAddress, '@') + 1);
        if (checkdnsrr($domain) !== FALSE) {
            $this->logger->info('AppBundle\Services\EmailsService\validateDomain() - Domain exists: ' . $domain);
            return true;
        } else {
            $this->logger->info('AppBundle\Services\EmailsService\validateDomain() - Domain does not exist: ' . $domain);
            return false;
        }
    }

    public function createEmailsArray($emailsString) {
        $this->logger->info('AppBundle\Services\EmailsService\createEmailsArray() - Start');
        $this->logger->info('AppBundle\Services\EmailsService\createEmailsArray() - Emails String: ' . serialize($emailsString));

        $emailPattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i';
        preg_match_all($emailPattern, $emailsString, $identifiedEmails);
        $identifiedEmailsArray = array();
        foreach ($identifiedEmails[0] as $processEmail) {
            $this->logger->info('AppBundle\Services\EmailsService\createEmailsArray() - Processing: ' . $processEmail);
            if (self::validateDomain($processEmail) === false) {                
                throw new \Exception("The following email address appears incorrect: <br /> " . $processEmail . " <br /> Please correct or remove it and try again");
            }
            $identifiedUsers = $this->doctrine->getRepository('AppBundle:Users')->findBy(array('email' => $processEmail));
            if (count($identifiedUsers) == 0 || count($identifiedUsers) > 1) {
                $newEmail = array();
                $newEmail['email'] = $processEmail;
            } else {
                $newEmail = array();
                $newEmail['email'] = $processEmail;
                $newEmail['name'] = $identifiedUsers[0]->getFirstname() . ' ' . $identifiedUsers[0]->getLastname();
                $this->logger->info('AppBundle\Services\EmailsService\createEmailsArray() - Found Alias: ' . $newEmail['name']);
            }
            array_push($identifiedEmailsArray, $newEmail);
        }
        if (count($identifiedEmailsArray) == 0) {
            return null;
        } else {
            return $identifiedEmailsArray;
        }
    }

    public function prepareEmailForQueue($inputParams) {
        $this->logger->info('AppBundle\Services\EmailsService\prepareEmailForQueue() - Start');

        $receivedEmailFrom          = trim($inputParams['EMAIL_FROM']);
        $receivedEmailTo            = $inputParams['EMAIL_TO'];
        $receivedEmailCc            = $inputParams['EMAIL_CC'];
        $receivedSubject            = $inputParams['SUBJECT'];
        $receivedBody               = $inputParams['BODY'];
        if (isset($inputParams['BODYHTML']) && $inputParams['BODYHTML'] != '') {
            $receivedBodyHtml       = $inputParams['BODYHTML'];            
        }
        $receivedAttachmentPath     = $inputParams['ATTACHMENT_PATH'];
        $receivedAttachmentName     = $inputParams['ATTACHMENT_NAME'];
        $receivedAttachmentSourceId = $inputParams['ATTACHMENT_SOURCEID'];

        $this->logger->info('AppBundle\Services\EmailsService\prepareEmailForQueue() - EMAIL_FROM: ' . $receivedEmailFrom);


        
        $userEntity = $this->tokenStorage->getToken()->getUser();

        $newEmailContent = array();
        if ($userEntity->getEmail() != $this->paramEmailFrom) {
                $this->logger->info('AppBundle\Services\EmailsService\prepareEmailForQueue() - FROM: Webcampak sent on behalf of: ' . $userEntity->getFirstname() . ' ' . $userEntity->getLastName());
                $newEmailContent['FROM']['name'] = 'Webcampak on behalf of ' .  $userEntity->getFirstname() . ' ' . $userEntity->getLastName();
                $newEmailContent['FROM']['email'] = $userEntity->getEmail();
        } else {
            $newEmailContent['FROM']['name'] = 'Webcampak';
            $newEmailContent['FROM']['email'] = $userEntity->getEmail();
        }

        $this->logger->info('AppBundle\Services\EmailsService\prepareEmailForQueue() - Create Email TO');        
        $newEmailContent['TO'] = self::createEmailsArray($receivedEmailTo);
        $this->logger->info('AppBundle\Services\EmailsService\prepareEmailForQueue() - Create Email CC');                
        $newEmailContent['CC'] = self::createEmailsArray($receivedEmailCc);

        if ($userEntity->getEmail() != '') {
            $newEmailContent['BODY'] = $receivedBody;
            if (isset($receivedBodyHtml)) {$newEmailContent['BODYHTML'] = $receivedBodyHtml;}
            $newEmailContent['SUBJECT'] = $receivedSubject;

            //Check if the user is allowed to access the source associated to the attachment being sent
            if ($receivedAttachmentPath > '' && $receivedAttachmentName != '' && intval($receivedAttachmentSourceId) > 0) {
                if ($this->sourceService->isUserAllowed($receivedAttachmentSourceId)) {
                    $attachmentFullPath = $this->dirSources . 'source' . $receivedAttachmentSourceId . $receivedAttachmentPath;
                    if (is_file($attachmentFullPath)) {
                        $newEmailContent['ATTACHMENTS'] = array(array('PATH' => $attachmentFullPath, 'NAME' => $receivedAttachmentName));
                    }
                } else {
                    throw new Exception("ERROR: You are not allowed to access this source (" . $receivedAttachmentSourceId . ")");
                }
            }

            $currentDate = \DateTime::createFromFormat('U.u', microtime(true));

            //Create a MD5 hash used to identify duplicate emails sent in one batch (and avoid spamming an email address by error).
            $emailHash = md5(json_encode($newEmailContent['TO'], JSON_FORCE_OBJECT) . json_encode($newEmailContent['CC'], JSON_FORCE_OBJECT) . json_encode($newEmailContent['SUBJECT'], JSON_FORCE_OBJECT));
            
            $newEmail = array('status' => 'queued', 'hash' => $emailHash, 'content' => $newEmailContent, 'logs' => array());
            
            $fs = new Filesystem();
            $fs->dumpFile($this->dirEmails . '/queued/' . $currentDate->format("Y-m-d_His_u") . '.json', json_encode($newEmail, JSON_FORCE_OBJECT));
            $results = array("success" => true, "message" => "Email queued");
        } else {
            $results = array("success" => false, "message" => "Your email address is missing in the system, unable to send email");
        }

        return $results;
    }

}
