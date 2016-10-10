<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use \DateTime;

class SyncReportsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, UserService $userService, ConfigurationService $configurationService, $paramDirSyncReports, $paramDirSources, $paramDirConfig) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->userService                  = $userService;        
        $this->configurationService         = $configurationService;        
        $this->paramDirSyncReports          = $paramDirSyncReports;
        $this->paramDirSources              = $paramDirSources;
        $this->paramDirConfig               = $paramDirConfig;
    }

    public function getSyncReportsList(\AppBundle\Entities\Database\Users $userEntity) {
        $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList()');
        
        $userReports = array();
        $userReportsFiles = array();
        
        $userSources = $this->userService->getCurrentSourcesByUseId($userEntity->getUseId());

        $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'queued/'));
        $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'process/'));
        foreach ($userSources as $userSource) {
            $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSources . 'source' . $userSource['SOURCEID'] . '/resources/sync-reports/'));            
        }
                
        foreach ($userReportsFiles as $reportFile) {
            $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList() - File: ' . $reportFile);
            $reportContent = self::readReportFile($reportFile);
            
            $reportFilePathInfo = pathinfo($reportFile);
                        
            if (isset($reportContent['result']['source']['files']['size']['total'])) {$reportContentSrcSize = $reportContent['result']['source']['files']['size']['total'];}
            else {$reportContentSrcSize = '';}                 
            if (isset($reportContent['result']['destination']['files']['size']['total'])) {$reportContentDstSize = $reportContent['result']['destination']['files']['size']['total'];}
            else {$reportContentDstSize = '';}                 
                                                
            array_push($userReports, array(
                'NAME' => self::formatReportValue($reportContent['job']['name'])
                , 'XFER' => false
                , 'STATUS' => self::formatReportValue($reportContent['job']['status'])
                , 'FILENAME' => $reportFilePathInfo['basename']
                , 'LOGS' => json_encode(self::formatReportValue($reportContent['job']['logs']))
                , 'DATE_QUEUED' => self::formatReportValue($reportContent['job']['date_queued'])
                , 'DATE_START' => self::formatReportValue($reportContent['job']['date_start'])
                , 'DATE_COMPLETED' => self::formatReportValue($reportContent['job']['date_completed'])
                    
                , 'SRC_SOURCEID' => self::formatReportValue($reportContent['job']['source']['sourceid'])
                , 'SRC_TYPE' => self::formatReportValue($reportContent['job']['source']['type'])
                , 'SRC_FTPSERVERID' => self::formatReportValue($reportContent['job']['source']['ftpserverid'])
                , 'SRC_SIZE' => $reportContentSrcSize
                , 'SRC_RESULT_FILES_COUNT_JPG' => self::formatReportValue($reportContent['result']['source']['files']['count']['jpg'])
                , 'SRC_RESULT_FILES_COUNT_RAW' => self::formatReportValue($reportContent['result']['source']['files']['count']['raw'])
                , 'SRC_RESULT_FILES_COUNT_TOTAL' => self::formatReportValue($reportContent['result']['source']['files']['count']['total'])
                , 'SRC_RESULT_FILES_SIZE_JPG' => self::formatReportValue($reportContent['result']['source']['files']['size']['jpg'])
                , 'SRC_RESULT_FILES_SIZE_RAW' => self::formatReportValue($reportContent['result']['source']['files']['size']['raw'])
                , 'SRC_RESULT_FILES_SIZE_TOTAL' => self::formatReportValue($reportContent['result']['source']['files']['size']['total'])     
                , 'SRC_RESULT_MISSING_COUNT_JPG' => self::formatReportValue($reportContent['result']['source']['missing']['count']['jpg'])
                , 'SRC_RESULT_MISSING_COUNT_RAW' => self::formatReportValue($reportContent['result']['source']['missing']['count']['raw'])
                , 'SRC_RESULT_MISSING_COUNT_TOTAL' => self::formatReportValue($reportContent['result']['source']['missing']['count']['total'])
                , 'SRC_RESULT_MISSING_SIZE_JPG' => self::formatReportValue($reportContent['result']['source']['missing']['size']['jpg'])
                , 'SRC_RESULT_MISSING_SIZE_RAW' => self::formatReportValue($reportContent['result']['source']['missing']['size']['raw'])
                , 'SRC_RESULT_MISSING_SIZE_TOTAL' => self::formatReportValue($reportContent['result']['source']['missing']['size']['total'])                     

                    
                , 'DST_SOURCEID' => self::formatReportValue($reportContent['job']['destination']['sourceid'])
                , 'DST_TYPE' => self::formatReportValue($reportContent['job']['destination']['type'])
                , 'DST_FTPSERVERID' => self::formatReportValue($reportContent['job']['destination']['ftpserverid'])
                , 'DST_SIZE' => $reportContentDstSize          
                , 'DST_RESULT_FILES_COUNT_JPG' => self::formatReportValue($reportContent['result']['destination']['files']['count']['jpg'])
                , 'DST_RESULT_FILES_COUNT_RAW' => self::formatReportValue($reportContent['result']['destination']['files']['count']['raw'])
                , 'DST_RESULT_FILES_COUNT_TOTAL' => self::formatReportValue($reportContent['result']['destination']['files']['count']['total'])
                , 'DST_RESULT_FILES_SIZE_JPG' => self::formatReportValue($reportContent['result']['destination']['files']['size']['jpg'])
                , 'DST_RESULT_FILES_SIZE_RAW' => self::formatReportValue($reportContent['result']['destination']['files']['size']['raw'])
                , 'DST_RESULT_FILES_SIZE_TOTAL' => self::formatReportValue($reportContent['result']['destination']['files']['size']['total'])     
                , 'DST_RESULT_MISSING_COUNT_JPG' => self::formatReportValue($reportContent['result']['destination']['missing']['count']['jpg'])
                , 'DST_RESULT_MISSING_COUNT_RAW' => self::formatReportValue($reportContent['result']['destination']['missing']['count']['raw'])
                , 'DST_RESULT_MISSING_COUNT_TOTAL' => self::formatReportValue($reportContent['result']['destination']['missing']['count']['total'])
                , 'DST_RESULT_MISSING_SIZE_JPG' => self::formatReportValue($reportContent['result']['destination']['missing']['size']['jpg'])
                , 'DST_RESULT_MISSING_SIZE_RAW' => self::formatReportValue($reportContent['result']['destination']['missing']['size']['raw'])
                , 'DST_RESULT_MISSING_SIZE_TOTAL' => self::formatReportValue($reportContent['result']['destination']['missing']['size']['total'])  
                    
                , 'ITR_RESULT_COUNT_JPG' => self::formatReportValue($reportContent['result']['intersect']['count']['jpg'])
                , 'ITR_RESULT_COUNT_RAW' => self::formatReportValue($reportContent['result']['intersect']['count']['raw'])
                , 'ITR_RESULT_COUNT_TOTAL' => self::formatReportValue($reportContent['result']['intersect']['count']['total'])
                , 'ITR_RESULT_SIZE_JPG' => self::formatReportValue($reportContent['result']['intersect']['size']['jpg'])
                , 'ITR_RESULT_SIZE_RAW' => self::formatReportValue($reportContent['result']['intersect']['size']['raw'])
                , 'ITR_RESULT_SIZE_TOTAL' => self::formatReportValue($reportContent['result']['intersect']['size']['total'])                          
            ));                           
        }
                        
        return $userReports;
    }

    public function formatReportValue($reportValue) {
        if (isset($reportValue)){return $reportValue;} 
        else {return '';}
    }    
    
    public function getReports($searchDirectory) {
        $this->logger->info('AppBundle\Services\SyncReportsService\getProcessReports()');        
        $reports = array();
        if (is_dir($searchDirectory)) {
            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('*.jso*');
            $finder->depth('== 0');
            $finder->in($searchDirectory);
            foreach ($finder as $file) {   
                array_push($reports, $file->getRealpath());
                $this->logger->info($file->getRealpath());                        
            }              
        }      
        return $reports;
    }     

    public function createSyncReport(\AppBundle\Entities\Database\Users $userEntity, $inputParams) {
        $this->logger->info('AppBundle\Services\SyncReportsService\createSyncReport()');        

        if ($this->userService->hasUserAccessToSourceId($userEntity, $inputParams['SRC_SOURCEID']) === true 
                && $this->userService->hasUserAccessToSourceId($userEntity, $inputParams['DST_SOURCEID']) === true) {
            $this->logger->info('AppBundle\Services\SyncReportsService\createSyncReport() - Access to the SRC and DST sources verified');

            // This hash is used to verify if a similar job is already waiting in the queue or process
            $jobHash = md5($inputParams['SRC_SOURCEID'] 
                                                . '-'. $inputParams['SRC_TYPE'] 
                                                . '-'. $inputParams['SRC_FTPSERVERID']
                                                . '-'. $inputParams['DST_SOURCEID']
                                                . '-'. $inputParams['DST_TYPE']
                                                . '-'. $inputParams['DST_FTPSERVERID']); 

            if (self::searchSyncReportInQueue($jobHash) === false) {
                $serverTimezone = $this->configurationService->getSourceConfigurationParameterValue($this->paramDirConfig . 'config-general.cfg', 'cfgservertimezone');
                $currentDate = new \DateTime('now', new \DateTimeZone($serverTimezone));
                
                $jsonReportJob = array();
                $jsonReportJob['job']['status'] = 'queued';
                $jsonReportJob['job']['name'] = $inputParams['NAME'];
                $jsonReportJob['job']['hash'] = $jobHash;
                $jsonReportJob['job']['xfer'] = $inputParams['XFER'];
                $jsonReportJob['job']['logs'] = array();
                
                $jsonReportJob['job']['source']['sourceid'] = $inputParams['SRC_SOURCEID'];
                $jsonReportJob['job']['source']['type'] = $inputParams['SRC_TYPE'];
                $jsonReportJob['job']['source']['ftpserverid'] = $inputParams['SRC_FTPSERVERID'];
                $jsonReportJob['job']['destination']['sourceid'] = $inputParams['DST_SOURCEID'];
                $jsonReportJob['job']['destination']['type'] = $inputParams['DST_TYPE'];
                $jsonReportJob['job']['destination']['ftpserverid'] = $inputParams['DST_FTPSERVERID']; 
                $jsonReportJob['job']['date_queued'] = $currentDate->format('c');
                $jsonReportJob['job']['date_start'] = '';
                $jsonReportJob['job']['date_completed'] = '';
                
                $jsonReportJob['result'] = array();
                $jsonReportJob['result']['source'] = array();
                $jsonReportJob['result']['source']['files'] = array();
                $jsonReportJob['result']['source']['files']['count'] = array();
                $jsonReportJob['result']['source']['files']['count']['jpg'] = 0;
                $jsonReportJob['result']['source']['files']['count']['raw'] = 0;
                $jsonReportJob['result']['source']['files']['count']['total'] = 0;
                $jsonReportJob['result']['source']['files']['size'] = array();
                $jsonReportJob['result']['source']['files']['size']['jpg'] = 0;
                $jsonReportJob['result']['source']['files']['size']['raw'] = 0;
                $jsonReportJob['result']['source']['files']['size']['total'] = 0;
                $jsonReportJob['result']['source']['missing'] = array();                
                $jsonReportJob['result']['source']['missing']['count'] = array();
                $jsonReportJob['result']['source']['missing']['count']['jpg'] = 0;
                $jsonReportJob['result']['source']['missing']['count']['raw'] = 0;
                $jsonReportJob['result']['source']['missing']['count']['total'] = 0;
                $jsonReportJob['result']['source']['missing']['size'] = array();
                $jsonReportJob['result']['source']['missing']['size']['jpg'] = 0;
                $jsonReportJob['result']['source']['missing']['size']['raw'] = 0;
                $jsonReportJob['result']['source']['missing']['size']['total'] = 0;                    
                $jsonReportJob['result']['destination'] = array();
                $jsonReportJob['result']['destination']['files'] = array();
                $jsonReportJob['result']['destination']['files']['count'] = array();
                $jsonReportJob['result']['destination']['files']['count']['jpg'] = 0;
                $jsonReportJob['result']['destination']['files']['count']['raw'] = 0;
                $jsonReportJob['result']['destination']['files']['count']['total'] = 0;
                $jsonReportJob['result']['destination']['files']['size'] = array();
                $jsonReportJob['result']['destination']['files']['size']['jpg'] = 0;
                $jsonReportJob['result']['destination']['files']['size']['raw'] = 0;
                $jsonReportJob['result']['destination']['files']['size']['total'] = 0;  
                $jsonReportJob['result']['destination']['missing'] = array();                
                $jsonReportJob['result']['destination']['missing']['count'] = array();
                $jsonReportJob['result']['destination']['missing']['count']['jpg'] = 0;
                $jsonReportJob['result']['destination']['missing']['count']['raw'] = 0;
                $jsonReportJob['result']['destination']['missing']['count']['total'] = 0;
                $jsonReportJob['result']['destination']['missing']['size'] = array();
                $jsonReportJob['result']['destination']['missing']['size']['jpg'] = 0;
                $jsonReportJob['result']['destination']['missing']['size']['raw'] = 0;
                $jsonReportJob['result']['destination']['missing']['size']['total'] = 0;                   
                $jsonReportJob['result']['intersect'] = array();
                $jsonReportJob['result']['intersect']['count'] = array();
                $jsonReportJob['result']['intersect']['count']['jpg'] = 0;
                $jsonReportJob['result']['intersect']['count']['raw'] = 0;
                $jsonReportJob['result']['intersect']['count']['total'] = 0;                
                $jsonReportJob['result']['intersect']['size'] = array();     
                $jsonReportJob['result']['intersect']['size']['jpg'] = 0;
                $jsonReportJob['result']['intersect']['size']['raw'] = 0;
                $jsonReportJob['result']['intersect']['size']['total'] = 0;                  
                          
                $currentDate = \DateTime::createFromFormat('U.u', microtime(true));
                $fs = new Filesystem();
                $fs->dumpFile($this->paramDirSyncReports . 'queued/' . $currentDate->format("Y-m-d_His_u") . '.json', json_encode($jsonReportJob, JSON_FORCE_OBJECT));

                return self::getSyncReportsList($userEntity);
            } else {
                return array("success" => false, "message" => "A similar report already exists in queue, please wait for end of execution or delete the job");                
            }
        } else {
            return array("success" => false, "message" => "You don't have permission to create a report for one of those sources");
        }
    }    

    public function removeSyncReport(\AppBundle\Entities\Database\Users $userEntity, $inputParams) {
        $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport()');

        if ($this->userService->hasUserAccessToSourceId($userEntity, $inputParams['SRC_SOURCEID']) === true 
                && $this->userService->hasUserAccessToSourceId($userEntity, $inputParams['DST_SOURCEID']) === true) {
            $this->logger->info('AppBundle\Services\SyncReportsService\createSyncReport() - Access to the SRC and DST sources verified');
            
            $userSources = $this->userService->getCurrentSourcesByUseId($userEntity->getUseId());          
            $userReportsFiles = array();            
            $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'queued/'));
            $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'process/'));
            foreach ($userSources as $userSource) {
                $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSources . 'source' . $userSource['SOURCEID'] . '/resources/sync-reports/'));            
            }
            foreach ($userReportsFiles as $reportFile) {
                $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList() - File: ' . $reportFile);
                $reportFilePathInfo = pathinfo($reportFile);
                if ($inputParams['FILENAME'] === $reportFilePathInfo['basename']) {
                    $fs = new Filesystem();
                    $fs->remove($reportFile);
                    return array("success" => true, "message" => "Report deleted");
                }
            }
            return array("success" => true, "message" => "Unable to find report file on the filesystem");
        } else {
            return array("success" => false, "message" => "You don't have permission to create a report for one of those sources");
        }
    }   
    
    public function searchSyncReportInQueue($hash) {
        $this->logger->info('AppBundle\Services\SyncReportsService\searchSyncReportInQueue(): ' . $hash);
        $reportFound = false;
        $userReportsFiles = array();       
        $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'queued/'));
        $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'process/'));
        foreach ($userReportsFiles as $reportFile) {
            $this->logger->info('AppBundle\Services\SyncReportsService\searchSyncReportInQueue() - Testing File: ' . $reportFile);
            $reportContent = self::readReportFile($reportFile);
            if (isset($reportContent['job']['hash']) && $reportContent['job']['hash'] === $hash) {                
                $reportFound = true;
            }
        }        
        return  $reportFound;        
    }
        
    public function readReportFile($filepath) {
        $this->logger->info('AppBundle\Services\SyncReportsService\readReportFile(): ' . $filepath);
        return json_decode(file_get_contents($filepath), true);    
    }
    
}
