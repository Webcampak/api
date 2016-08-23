<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Services\SourcesService;
use AppBundle\Services\FilesService;

class StatusService
{
    public function __construct(TokenStorage $tokenStorage, AuthorizationChecker $authorizationChecker, Doctrine $doctrine, Logger $logger, FilesService $filesService, SourcesService $sourcesService, DevicesService $devicesService, UserService $userService, ScheduleService $scheduleService, ConfigurationService $configurationService, StatsService $statsService, $kernelRootDir, $etcDir) {
        $this->tokenStorage         = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;        
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
        $this->filesService    = $filesService;
        $this->sourcesService  = $sourcesService;
        $this->devicesService  = $devicesService;
        $this->userService     = $userService;
        $this->statsService    = $statsService;
        $this->scheduleService      = $scheduleService;
        $this->configurationService = $configurationService;
        $this->kernelRootDir        = $kernelRootDir;
        $this->etcDir               = $etcDir;
    }

    public function getAuthenticationStatus(Controller $sourceController, $receivedUsername) {
        $this->logger->info('AppBundle\Services\StatusService\getAuthenticationStatus() - Start');
        $userEntity = $this->tokenStorage->getToken()->getUser();

        if ($userEntity && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if ($receivedUsername == '' || $userEntity->getUsername() == $receivedUsername) {
                $resultAuthentication = array("success" => true
                     , "message" => "Server is online, user is authenticated on client and server"
                     , "status" => "AUTHENTICATED"
                     , "USERNAME" => $userEntity->getUsername());
            } else {
                $resultAuthentication = array("message" => "Server is online, there is a username mismatch, Client:" . $receivedUsername . " Server:" . $userEntity->getUsername() . " closing server session", "status" => "SESSIONMISTMATCH");
                //User mismatch, can represent a security risk we de-authenticate the client
                $this->logger->info('AppBundle\Controller\OnlineStatusController.php\indexAction() - We close the clients session on the server');
                $sourceController->get('security.token_storage')->setToken(null);
                $sourceController->get('request')->getSession()->invalidate();
            }

        } else {
            $resultAuthentication = array("message" => "Server is online, user not authenticated on server", "status" => "NOTAUTHENTICATED");
        }
        return $resultAuthentication;
    }

    public function getSystemUptime() {
        $this->logger->info('AppBundle\Services\StatusService\getSystemStartup() - Start');
        $tmp = explode(' ', file_get_contents('/proc/uptime'));
        return time() - intval($tmp[0]);
    }
    
    // Create a current date and substract time since boot in seconds
    public function getSystemBootDate() {
        $this->logger->info('AppBundle\Services\StatusService\getSystemStartTime() - Start');        
        $tmp = explode(' ', file_get_contents('/proc/uptime'));
        $currentDate = \DateTime::createFromFormat('U.u', microtime(true));
        $currentDate->sub(new \DateInterval('PT' . intval($tmp[0]) . 'S'));
        return $currentDate->format('c') ;
    }    
    
    public function getBuildVersion() {
        $this->logger->info('AppBundle\Services\StatusService\getAuthenticationStatus() - Start');

        if (is_file($this->kernelRootDir. '/../../../../build.txt')) {
            $resultBuild = file_get_contents($this->kernelRootDir. '/../../../../build.txt');
            $resultBuild = preg_replace('/[^(\x20-\x7F)]*/','', $resultBuild);
            $this->logger->info('AppBundle\Controller\StatusController\indexAction.php\getSettingsAction() - Current Build: ' . $resultBuild);
        } else {
            $resultBuild = "dev";
        }
        return $resultBuild;
    }

    public function getDiskStatus() {
        $this->logger->info('AppBundle\Services\StatusService\getDiskStatus() - Start');

        $resultDisk = array(
            "Total" => disk_total_space($this->kernelRootDir)
            , "Free" => disk_free_space($this->kernelRootDir)
            , "Used" => disk_total_space($this->kernelRootDir) - disk_free_space($this->kernelRootDir)
        );
        return $resultDisk;
    }

    public function getCameras() {
        $this->logger->info('AppBundle\Services\StatusService\getCameras() - Start');

        return $this->devicesService->getUsbPorts();
    }    
    
    public function getSourcesStatus() {
        $this->logger->info('AppBundle\Services\StatusService\getSourcesStatus() - Start');

        $userEntity = $this->tokenStorage->getToken()->getUser();

        
        $userSources = $this->userService->getCurrentSourcesByUseId($userEntity->getUseId());
        foreach($userSources as $idx=>$sourceConfig) {
            $sourceTimezone = $this->configurationService->getSourceConfigurationParameterValue($this->etcDir . 'config-source' . $sourceConfig['SOURCEID'] . '.cfg', 'cfgcapturetimezone');
            
            $sourceCaptureFrequency = $this->configurationService->getSourceConfigurationParameterValue($this->etcDir . 'config-source' . $sourceConfig['SOURCEID'] . '.cfg', 'cfgcroncapturevalue');
            $sourceCaptureInterval = $this->configurationService->getSourceConfigurationParameterValue($this->etcDir . 'config-source' . $sourceConfig['SOURCEID'] . '.cfg', 'cfgcroncaptureinterval');
            if ($sourceCaptureInterval == 'minutes') {
                $sourceCaptureRate = $sourceCaptureFrequency . ' mn';
            } else {
                $sourceCaptureRate = $sourceCaptureFrequency . ' s';                
            }
            $userSources[$idx]['capture']['rate'] = $sourceCaptureRate;
            
            //Get last capture file
            $userSources[$idx]['capture']['last']['filename'] = $this->sourcesService->getLastPictureForSource($sourceConfig['SOURCEID']);

            $userSources[$idx]['capture']['last']['date'] = \DateTime::createFromFormat('YmdHis', substr($userSources[$idx]['capture']['last']['filename'], 0,14), new \DateTimeZone($sourceTimezone));            
            if ($userSources[$idx]['capture']['last']['date'] instanceof \DateTime) {
              $userSources[$idx]['capture']['last']['date'] = $userSources[$idx]['capture']['last']['date']->format('c');
            } else {
               $userSources[$idx]['capture']['last'] = false; 
               $userSources[$idx]['capture']['next'] = false;                
            }
            
            // Get next capture file
            if ($userSources[$idx]['capture']['last'] !== false) {
                $sourceHasSchedule = $this->sourcesService->checkSourceScheduleExists($sourceConfig['SOURCEID']);  
                $lastPictureDate = \DateTime::createFromFormat('YmdHis', substr($userSources[$idx]['capture']['last']['filename'], 0,14), new \DateTimeZone($sourceTimezone));                                                
                $nextCaptureFromPictureDate = $this->scheduleService->getNextCaptureSlot($sourceHasSchedule, $lastPictureDate, false);
                if ($nextCaptureFromPictureDate !== false) {
                    $userSources[$idx]['capture']['next']['date'] = $nextCaptureFromPictureDate->format('c');                                
                } else {
                    $userSources[$idx]['capture']['next'] = $nextCaptureFromPictureDate;                
                }                
            }

            
            $userSources[$idx]['disk']['Used'] = $this->sourcesService->getSourceDirectorySize($sourceConfig['SOURCEID']);
            if (intval($sourceConfig['QUOTA']) === 0) {
                $userSources[$idx]['disk']['Total'] = disk_total_space($this->kernelRootDir);
            } else {
                $userSources[$idx]['disk']['Total'] = intval($sourceConfig['QUOTA']);
            }
            $userSources[$idx]['disk']['Free'] = $userSources[$idx]['disk']['Total'] - $this->sourcesService->getSourceDirectorySize($sourceConfig['SOURCEID']);
            
            $userSources[$idx]['history']['size'] = array_reverse($this->statsService->getSourcesDiskUsage($sourceConfig['SOURCEID'], 15));
            $userSources[$idx]['history']['count'] = array_reverse($this->statsService->getSourcesPicturesCountSize($sourceConfig['SOURCEID'], 15));
                        
        }
        return $userSources;
    }

}
