<?php
/*
Copyright 2010-2015 Eurotechnia (support@webcampak.com)
This file is part of the Webcampak project.
Webcampak is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License,
or (at your option) any later version.

Webcampak is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Webcampak.
If not, see http://www.gnu.org/licenses/.
*/
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SendEmailsCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:sendemails')
            ->setDescription('Process the email queue and send emails')
            ->addArgument('sleep', InputArgument::OPTIONAL, 'Sleep before starting to process the queue (seconds)')                
        ;
    }

    function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }
        
    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|       PROCESS THE EMAIL QUEUE AND SEND EMAILS        |');
        self::log($output, 'info', '--------------------------------------------------------');

        //Fake the authentication mechanism and act as root
        $searchRootUserEntity = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Users')->findOneByUsername('root');                        
        $token = new UsernamePasswordToken($searchRootUserEntity, null, "secured_area", $searchRootUserEntity->getRoles());            
        $this->getContainer()->get("security.token_storage")->setToken($token);          
        
        $sleepTime = $input->getArgument('sleep');
        if ($sleepTime) {
            self::log($output, 'info', 'Program will sleep for ' . $sleepTime . ' seconds');
            sleep($sleepTime);
        }        

        $serverTimezone = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-general.cfg', 'cfgservertimezone');            
        
        $fs = new Filesystem();        
        
        $emailDir = $this->getContainer()->getParameter('dir_emails');
        $currentFileDir = $emailDir . 'queued/';
        $finder = new Finder();
        $finder->files();
        $finder->sortByName();
        $finder->files()->name('*.json');
        $finder->in($emailDir . 'queued/');
        $emailHashTable = array();// Used to store emails to be sent in batch
        foreach ($finder as $file) {
            self::log($output, 'info', 'SendEmailsCommand.php\execute() - Looking at file: ' . $file->getFilename());            
            $currentFileDir = $emailDir . 'failed/';
            $currentFileName = $file->getFilename();
                        
            $emailContent = json_decode($file->getContents(), true);
            
            //Check if same email has been sent too many times
            $currentHash = $emailContent['hash'];
            if (!isset($emailHashTable[$currentHash]['count'])) {$emailHashTable[$currentHash]['count'] = 0;}
            
            self::log($output, 'info', 'SendEmailsCommand.php\execute() - Email Hash is: ' . $currentHash);
            
            $emailContent['status'] = 'process';
            
            //1- Move the file to the failed directory, any file remaining here for too long can be considered failed
            $fs->dumpFile($currentFileDir . $currentFileName, json_encode($emailContent));
            $fs->remove($file); 

            $currentDate = new \DateTime('now', new \DateTimeZone($serverTimezone));
            $emailContent['process'] = $currentDate->format('c');            
                
            if ($emailHashTable[$currentHash]['count'] <= 4 ) {
                $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Email file moved to processing directory');
                self::processEmail($output, $emailContent, $currentFileDir, $currentFileName, $serverTimezone);                
            } else {
                if (!isset($emailHashTable[$currentHash]['email'])) {
                    $emailHashTable[$currentHash]['email'] = $emailContent;
                    $emailHashTable[$currentHash]['currentFileDir'] = $currentFileDir;
                    $emailHashTable[$currentHash]['currentFileName'] = $currentFileName;
                } else {
                    $emailHashTable[$currentHash]['email']['content']['BODY'] = $emailHashTable[$currentHash]['email']['content']['BODY'] . '---------- EMAIL PART OF A BATCH ------------' . $emailContent['content']['BODY'];
                }                
            }
            $emailHashTable[$currentHash]['count']++;
        } 
        self::log($output, 'info', 'SendEmailsCommand.php\execute() - Finished processing queue, going through batch');
        
        foreach ($emailHashTable as $currentHash=>$currentEmail) {    
            if ($currentEmail['count'] > 4) {
                self::log($output, 'info', 'SendEmailsCommand.php\execute() - Processing Hash: ' . $currentHash);               
                self::processEmail($output, $currentEmail['email'], $currentEmail['currentFileDir'],  $currentEmail['currentFileName'], $serverTimezone);                                                
            }
        }
    }

    function processLog(OutputInterface $output, $emailContent, $file, $tz, $logMessage) {
        self::log($output, 'info', 'AlertsCommand.php\processLog() - ' . $logMessage);

        $fs = new Filesystem();                
        $currentDate = new \DateTime('now', new \DateTimeZone($tz));
        
        if (!isset($emailContent['logs']) || count($emailContent['logs']) == 0) {$emailContent['logs'] = array();}
        array_push($emailContent['logs'], array(
            'date' => $currentDate->format('c')
            , 'message' => $logMessage        
        ));
        
        $fs->dumpFile($file, json_encode($emailContent));
        return $emailContent;
    }
    
    function processEmail(OutputInterface $output, $emailContent, $currentFileDir, $currentFileName, $serverTimezone) {
        self::log($output, 'info', 'AlertsCommand.php\sendEmail()');
        $fs = new Filesystem();
        
        // We send an email with all details
        $newEmail = \Swift_Message::newInstance();
        
        // Set Sender
        $newEmail->setSender(array($this->getContainer()->getParameter('mailer_from') => $this->getContainer()->getParameter('mailer_from_name')));
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Set Sender');
        
        // Set From
        if (isset($emailContent['content']['FROM']['name'])) {
            $newEmail->setFrom(array($emailContent['content']['FROM']['email'] => $emailContent['content']['FROM']['name']));   
            $newEmail->setReplyTo(array($emailContent['content']['FROM']['email'] => $emailContent['content']['FROM']['name']));            
        } else {
            $newEmail->setFrom(array($emailContent['content']['FROM']['email']));   
            $newEmail->setReplyTo(array($emailContent['content']['FROM']['email']));                        
        }
        $newEmail->setReturnPath($emailContent['content']['FROM']['email']);
        //$newEmail->setReplyTo(array($emailContent['content']['FROM']['email'] => $emailContent['content']['FROM']['name']));
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Set FROM field');
        
        // Set To
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: TO: Beginning setting TO field');        
        if (isset($emailContent['content']['TO']) && count($emailContent['content']['TO']) > 0) {
            foreach ($emailContent['content']['TO'] as $toEmail) {
                if (isset($toEmail['name'])) {
                    $newEmail->addTo($toEmail['email'], $toEmail['name']);
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: TO: Adding ' . $toEmail['email'] . ' (' . $toEmail['name'] . ')');                            
                } else {
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: TO: Adding ' . $toEmail['email']);                                                
                    $newEmail->addTo($toEmail['email']);
                }
            }
        } else {
            $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: TO: No TO field');        
        }
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: TO: Setting TO field completed');        
        
        // Set CC
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: CC: Beginning setting CC field');        
        if (isset($emailContent['content']['CC']) && count($emailContent['content']['CC']) > 0) {
            foreach ($emailContent['content']['CC'] as $ccEmail) {
                if (isset($ccEmail['name'])) {
                    $newEmail->addCc($ccEmail['email'], $ccEmail['name']);
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: CC: Adding ' . $ccEmail['email'] . ' (' . $ccEmail['name'] . ')');                                                
                } else {
                    $newEmail->addCc($ccEmail['email']);
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: CC: Adding ' . $ccEmail['email']);                                                                    
                }
            }            
        } else {
            $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: CC: No CC field');        
        }
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: CC: Setting CC field completed');

        // Set BCC
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: BCC: Beginning setting BCC field');        
        if (isset($emailContent['content']['BCC']) && count($emailContent['content']['BCC']) > 0) {
            foreach ($emailContent['content']['BCC'] as $bccEmail) {
                if (isset($bccEmail['name'])) {
                    $newEmail->addBcc($bccEmail['email'], $bccEmail['name']);
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: BCC: Adding ' . $bccEmail['email'] . ' (' . $bccEmail['name'] . ')');                                                
                } else {
                    $newEmail->addBcc($bccEmail['email']);
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: BCC: Adding ' . $bccEmail['email']);                                                                    
                }
            }            
        } else {
            $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: BCC: No BCC field');        
        }
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: BCC: Setting BCC field completed');        
        
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Setting Email Subject & Body');                
        $newEmail->setSubject($emailContent['content']['SUBJECT']);
        $newEmail->setBody($emailContent['content']['BODY'], 'text/plain');
        if (isset($emailContent['content']['BODYHTML']) && $emailContent['content']['BODYHTML'] != '') {
            $newEmail->addPart($emailContent['content']['BODYHTML'], 'text/html');            
        }
        $newEmail->setBody('<html><body><pre>' . $emailContent['content']['BODY'] . '</pre></body></html>', 'text/html');
           
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: Beginning processing attachments');                
        if (isset($emailContent['content']['ATTACHMENTS']) && count($emailContent['content']['ATTACHMENTS']) > 0) {
            foreach ($emailContent['content']['ATTACHMENTS'] as $emailAttachmment) {
                if (is_file($emailAttachmment['PATH']) && isset($emailAttachmment['WIDTH']) && $emailAttachmment['WIDTH'] != ""){
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: Resizing attachment');                
                    $originalPictureInfo = getimagesize($emailAttachmment['PATH']);
                    $originalPictureWidth = $originalPictureInfo[0];
                    $originalPictureHeight = $originalPictureInfo[1];
                    $pictureHeight = intval($emailAttachmment['WIDTH'] * $originalPictureHeight / $originalPictureWidth);
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: Original Size: ' . $originalPictureWidth . 'x' . $originalPictureHeight);                
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: Target Size: ' . $emailAttachmment['WIDTH'] . 'x' . $pictureHeight);                
                    
                    $dstPicture = \imagecreatetruecolor($emailAttachmment['WIDTH'], $pictureHeight);
                    $srcPicture = \imagecreatefromjpeg($emailAttachmment['PATH']);
                    \imagecopyresized($dstPicture, $srcPicture, 0, 0, 0, 0, $emailAttachmment['WIDTH'], $pictureHeight, $originalPictureWidth, $originalPictureHeight);
                    
                    $tmpPath = tempnam("/tmp", "PIC");
                    \imagejpeg($dstPicture, $tmpPath);
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $contentType = finfo_file($finfo, $emailAttachmment['PATH']);
                    finfo_close($finfo);
            
                    $attachment = \Swift_Attachment::newInstance(file_get_contents($tmpPath), $emailAttachmment['NAME'], $contentType);
                    $newEmail->attach($attachment);
                    unlink($tmpPath);
                } else if (is_file($emailAttachmment['PATH'])) {
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: Processing attachment: ' . $emailAttachmment['PATH'] . ' (' . $emailAttachmment['NAME'] . ')');                                                                    
                    $newEmail->attach(\Swift_Attachment::fromPath($emailAttachmment['PATH']));
                } else {
                    $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: Unable to access file: ' . $emailAttachmment['PATH']);
                }
            }            
        } else {
            $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: No Attchments in email');        
        }
        $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Swift: Attachments: Setting attachments completed');        

        try{
            $this->getContainer()->get('mailer')->send($newEmail);
            $emailContent['status'] = 'sent';
            $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Email Sent');   
            $currentDate = new \DateTime('now', new \DateTimeZone($serverTimezone));
            $emailContent['sent'] = $currentDate->format('c');
            
            $emailDir = $this->getContainer()->getParameter('dir_emails');
            if (!file_exists($emailDir .'sent/' . substr($currentFileName, 0,10) . '/')) {
                $fs->mkdir($emailDir .'sent/' . substr($currentFileName, 0,10) . '/', 0700);                
            }            
            $fs->dumpFile($emailDir .'sent/' . substr($currentFileName, 0,10) . '/' . $currentFileName, json_encode($emailContent));
            $fs->remove($currentFileDir . $currentFileName);             
            
        } catch(\Swift_TransportException $e){
            $emailContent['status'] = 'error';        
            $currentDate = new \DateTime('now', new \DateTimeZone($serverTimezone));
            $emailContent['error'] = $currentDate->format('c');            
            $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Unable to send email'); 
            $emailContent = self::processLog($output, $emailContent, $currentFileDir . $currentFileName, $serverTimezone, 'Error: ' . $e->getMessage()); 

        }
               
    }

    
}

