<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Filesystem;

use AppBundle\Entities\Database\Sources;
use AppBundle\Entities\Database\UsersSources;
use AppBundle\Command\SourceDeleteCommand;
use AppBundle\Command\SourceMoveCommand;
use AppBundle\Classes\BufferedOutput;


class SourcesService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, $paramDirSources, $paramDirWatermark, $paramDirEtc) {
        $this->tokenStorage      = $tokenStorage;
        $this->em                   = $doctrine->getManager();
        $this->logger               = $logger;
        $this->connection           = $doctrine->getConnection();
        $this->doctrine             = $doctrine;
        $this->paramDirSources      = $paramDirSources;
        $this->paramDirWatermark    = $paramDirWatermark;
        $this->paramDirEtc          = $paramDirEtc;
    }
    
    public function getSourceDirectorySize($souId) {
        $this->logger->info('AppBundle\Services\SourcesService\getSourceDirectorySize() - Start');
        $sourceDir = $this->paramDirSources . 'source' . $souId . '/';
        if (is_dir($sourceDir)) {
            $command = "du -b -s " . $sourceDir;
            $createConfiguration = new Process($command);
            $createConfiguration->run();
            return intval(explode("\t", $createConfiguration->getOutput())[0]);
        } else {
            return false;
        }
    }

    //Takes a sourceId and true of false depending if current user is allowed to access a specific source
    public function isUserAllowed($sourceId) {
        $this->logger->info('AppBundle\Services\SourcesService\isUserAllowed() - Start');
        $userEntity = $this->tokenStorage->getToken()->getUser();

        // Find Source ID
        $sourceEntity = $this->doctrine
            ->getRepository('AppBundle:Sources')
            ->findOneBySourceId($sourceId);

        $userSourcesEntity = $this->doctrine
            ->getRepository('AppBundle:UsersSources')
            ->findOneBy(array(
            'use' => $userEntity
            , 'sou' => $sourceEntity
        ));

        if ($userSourcesEntity) {
            $this->logger->info('AppBundle\Services\SourcesService\isUserAllowed() - Return: TRUE');
            return true;
        } else {
            $this->logger->info('AppBundle\Services\SourcesService\isUserAllowed() - Return: FALSE');
            return false;
        }
    }

    public function removeSource(Sources $sourceEntity, $controllerContainer) {
        $this->logger->info('AppBundle\Services\SourcesService\removeSource() - Start');

        $sourceDeleteCommand = new SourceDeleteCommand();
        $sourceDeleteCommand->setContainer($controllerContainer);
        $input = new ArrayInput(array('--sourceid' => $sourceEntity->getSourceId()));
        $output = new BufferedOutput();
        $resultCode = $sourceDeleteCommand->run($input, $output);
        $commandOutput = explode("\n", $output->getBuffer());
        foreach($commandOutput as $commandOutputLine) {
            $this->logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\removeSourceAction() - Console Subprocess: ' . $commandOutputLine);
        }
        $this->logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\removeSourceAction() - Command Result code: ' . $resultCode);

        if ($resultCode == 0) {
            $this->em->remove($sourceEntity);
            $this->em->flush();
            return array("success" => true, "message" => "Source deleted");
        } else {
            return array("success" => false, "title" => "Error", "message" => "Unable to remove source");                                                    
        }
    }

    public function moveSource(Sources $sourceEntity, $receivedSourceId, $controllerContainer) {
        $this->logger->info('AppBundle\Services\SourcesService\moveSource() - Start');

        $sourceMoveCommand = new SourceMoveCommand();
        $sourceMoveCommand->setContainer($controllerContainer);
        $input = new ArrayInput(array('--srcid' => $sourceEntity->getSourceId(), '--dstid' => $receivedSourceId));
        $output = new BufferedOutput();
        $resultCode = $sourceMoveCommand->run($input, $output);
        $commandOutput = explode("\n", $output->getBuffer());
        foreach($commandOutput as $commandOutputLine) {
            $this->logger->info('AppBundle\Services\SourcesService\moveSource() - Console Subprocess: ' . $commandOutputLine);
        }
        $this->logger->info('AppBundle\Services\SourcesService\moveSource() - Command Result code: ' . $resultCode);

    }


    public function addUserToSource($receivedSouId, $receivedUseId) {
        $this->logger->info('AppBundle\Services\SourcesService\isUserAllowed() - Start');
        if (isset($receivedSouId) && intval($receivedSouId) > 0 && isset($receivedUseId) && intval($receivedUseId)) {

            $userEntity = $this->doctrine
                            ->getRepository('AppBundle:Users')
                            ->find($receivedUseId);

            $sourceEntity = $this->doctrine
                                ->getRepository('AppBundle:Sources')
                                ->find($receivedSouId);

            $newUsersSourceEntity = new UsersSources();
            $newUsersSourceEntity->setSou($sourceEntity);
            $newUsersSourceEntity->setUse($userEntity);
            $newUsersSourceEntity->setAlertsFlag('N');
            

            $em = $this->doctrine->getManager();
            $em->persist($newUsersSourceEntity);
            $em->flush();

            return array("success" => true, "message" => "Source modification completed");
        } else {
            return array("success" => false, "title" => "Error", "message" => "No user selected");                                                                
        }
    }

    public function getWatermarkFiles($receivedSourceid) {
        $this->logger->info('AppBundle\Services\SourcesService\getWatermarkFiles() - Start');

        $watermarkfiles = array();
        //First we look into source directory
        if (is_dir($this->paramDirSources . "source" . $receivedSourceid . "/resources/watermark/")) {
            $watermarkdir = opendir($this->paramDirSources . "source" . $receivedSourceid . "/resources/watermark/");
            while ($listwatermarkfile = readdir($watermarkdir)) {
               if(is_file($this->paramDirSources . "source" . $receivedSourceid . "/resources/watermark/" . $listwatermarkfile) && (substr($listwatermarkfile, -4,4) == ".png" || substr($listwatermarkfile, -4,4) == ".jpg")) {
                    $tmpwatermarkfiles = array();
                    $tmpwatermarkfiles['NAME'] = $listwatermarkfile;
                    array_push($watermarkfiles, $tmpwatermarkfiles);
               }
            }
        }

        //Then we look into global resources directory
        if (is_dir($this->paramDirWatermark)) {
            $watermarkdir = opendir($this->paramDirWatermark);
            while ($listwatermarkfile = readdir($watermarkdir)) {
               if(is_file($this->paramDirWatermark.$listwatermarkfile) && (substr($listwatermarkfile, -4,4) == ".png" || substr($listwatermarkfile, -4,4) == ".jpg")) {
                    $tmpwatermarkfiles = array();
                    $tmpwatermarkfiles['NAME'] = $listwatermarkfile;
                    array_push($watermarkfiles, $tmpwatermarkfiles);
               }
            }
        }
        return $watermarkfiles;
    }

    public function getCurrentUsersBySouId($souId) {
        $this->logger->info('AppBundle\Services\SourcesService\getCurrentUsersBySouId()');
        $sqlQuery = "
            SELECT
                USE.USE_ID              USE_ID
                , USE.USERNAME          USERNAME
                , USE.FIRSTNAME         FIRSTNAME
                , USE.LASTNAME          LASTNAME
                , USESOU.SOU_ID         SOU_ID
                , USESOU.USESOU_ID      USESOU_ID
                , USESOU.ALERTS_FLAG    ALERTS_FLAG
            FROM
                USERS_SOURCES USESOU
            LEFT JOIN USERS USE ON USE.USE_ID = USESOU.USE_ID
            WHERE
                USESOU.SOU_ID = :receivedSouId
            ORDER BY USE.USERNAME
        ";
        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedSouId' => $souId));
        return $dbresults;
    }

    public function getAvailableUsersBySouId($souId) {
        $this->logger->info('AppBundle\Services\SourcesService\getAvailableUsersBySouId()');
        $sqlQuery = "
            SELECT
                USE.USE_ID          USE_ID
                , USE.USERNAME      USERNAME
                , USE.FIRSTNAME     FIRSTNAME
                , USE.LASTNAME      LASTNAME
                , :receivedSouId    SOU_ID
                , 'N'               ALERTS_FLAG
            FROM
                USERS USE
            WHERE
                USE_ID NOT IN (SELECT USE_ID FROM USERS_SOURCES WHERE SOU_ID = :receivedSouId)
            ORDER BY USE.USERNAME
        ";
        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedSouId' => $souId));
        return $dbresults;
    }

    public function checkSourceScheduleExists($sourceId) {
        $this->logger->info('AppBundle\Services\SourcesService\checkSourceScheduleExists() - Checking if source schedule configuration exists and is not empty');

        $scheduleFile = $this->paramDirEtc . 'config-source' . $sourceId . '-schedule.json';
        if (is_file($scheduleFile)) {
            $jsonContent = file_get_contents($scheduleFile);
            $scheduleArray = json_decode($jsonContent, true);
            if (count($scheduleArray) == 0) {
                $this->logger->info('AppBundle\Services\SourcesService\checkSourceScheduleExists() - ' . $sourceId . ' - Source has an EMPTY alert schedule');                
                return false;
            } else {
                $this->logger->info('AppBundle\Services\SourcesService\checkSourceScheduleExists() - ' . $sourceId . ' - Source has an alert schedule');                                
                return $scheduleArray;
            }
        } else {
            $this->logger->info('AppBundle\Services\SourcesService\checkSourceScheduleExists() - ' . $sourceId . ' - Source does not have an alert schedule configured');                                            
            return false;
        }
    }  
}
