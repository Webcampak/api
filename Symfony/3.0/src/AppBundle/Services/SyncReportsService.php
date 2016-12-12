<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use \DateTime;
use Symfony\Component\Process\Process;

class SyncReportsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, UserService $userService, ConfigurationService $configurationService, FtpService $ftpService, $paramDirSyncReports, $paramDirSources, $paramDirConfig, $paramDirEtc) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->userService                  = $userService;
        $this->configurationService         = $configurationService;
        $this->ftpService                   = $ftpService;
        $this->paramDirSyncReports          = $paramDirSyncReports;
        $this->paramDirSources              = $paramDirSources;
        $this->paramDirConfig               = $paramDirConfig;
        $this->paramDirEtc                  = $paramDirEtc;
    }

    public function getSyncReportsList(\AppBundle\Entities\Database\Users $userEntity) {
        $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList()');
        
        $userReports = array();

        //Create an array containing all report files, queued, in process and completed
        $userReportsFiles = array();
        $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'queued/'));
        $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'process/'));
        $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'completed/', 'summary.json'));

        $userSourcesFtp = array();
        foreach ($userReportsFiles as $reportFile) {
            $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList() - File: ' . $reportFile);
            $reportContent = self::readReportFile($reportFile);
            
            $reportFilePathInfo = pathinfo($reportFile);

            if ($reportContent['job']['xfer'] === true) {
                $cptTransferedFiles = self::getXferStatus($reportFile);
                $cptToBeTransferedFiles = self::formatReportValue($reportContent['result']['destination']['missing']['count']['total']);
                $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList() - Number of files already transferred for report: ' . $cptTransferedFiles);
                $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList() - Number of files to be transferred: ' . $cptToBeTransferedFiles);
                if (intval($cptTransferedFiles) === 0 || intval($cptToBeTransferedFiles) === 0 ) {
                    $prctCompleted = 0;
                } else {
                    $prctCompleted = round($cptTransferedFiles * 100 / $cptToBeTransferedFiles, 2);
                }
                $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList() - Percent Completed: ' . $prctCompleted);
                $xferStatus = $prctCompleted . "% (" . $cptTransferedFiles . " / " . $cptToBeTransferedFiles . ")";
            } else {
                $xferStatus = "n/a";
            }

            if (isset($reportContent['result']['source']['files']['size']['total'])) {$reportContentSrcSize = $reportContent['result']['source']['files']['size']['total'];}
            else {$reportContentSrcSize = '';}                 
            if (isset($reportContent['result']['destination']['files']['size']['total'])) {$reportContentDstSize = $reportContent['result']['destination']['files']['size']['total'];}
            else {$reportContentDstSize = '';}

            $srcSourceID = $reportContent['job']['source']['sourceid'];
            $dstSourceID = $reportContent['job']['source']['sourceid'];
            if (!isset($userSourcesFtp[$srcSourceID])) {
                $ftpServerConfigFile =  $this->paramDirEtc . "config-source" .$srcSourceID . "-ftpservers.cfg";
                $userSourcesFtp[$srcSourceID] = $this->ftpService->getServersFromConfigFile($ftpServerConfigFile);
            }
            if (!isset($userSourcesFtp[$dstSourceID])) {
                $ftpServerConfigFile =  $this->paramDirEtc . "config-source" .$dstSourceID . "-ftpservers.cfg";
                $userSourcesFtp[$dstSourceID] = $this->ftpService->getServersFromConfigFile($ftpServerConfigFile);
            }

            if (isset($reportContent['job']['source']['ftpserverid']) && intval($reportContent['job']['source']['ftpserverid']) > 0) {
                $srcName = "Unable to find";
                foreach ($userSourcesFtp[$srcSourceID] as $ftpServer) {
                    if (intval($ftpServer['ID']) === intval($reportContent['job']['source']['ftpserverid'])) {
                        $srcName = $ftpServer['NAME'];
                    }
                }
            } else {
                $srcName = 'filesystem';
            }
            if (isset($reportContent['job']['destination']['ftpserverid']) && intval($reportContent['job']['destination']['ftpserverid']) > 0) {
                $dstName = "Unable to find";
                foreach ($userSourcesFtp[$srcSourceID] as $ftpServer) {
                    if (intval($ftpServer['ID']) === intval($reportContent['job']['destination']['ftpserverid'])) {
                        $dstName = $ftpServer['NAME'];
                    }
                }
            } else {
                $dstName = 'filesystem';
            }

            array_push($userReports, array(
                'NAME' => self::formatReportValue($reportContent['job']['name'])
                , 'XFER' => $reportContent['job']['xfer']
                , 'XFER_STATUS' => $xferStatus
                , 'STATUS' => self::formatReportValue($reportContent['job']['status'])
                , 'FILENAME' => $reportFilePathInfo['basename']
                , 'LOGS' => json_encode(self::formatReportValue($reportContent['job']['logs']))
                , 'DATE_QUEUED' => self::formatReportValue($reportContent['job']['date_queued'])
                , 'DATE_START' => self::formatReportValue($reportContent['job']['date_start'])
                , 'DATE_COMPLETED' => self::formatReportValue($reportContent['job']['date_completed'])
                    
                , 'SRC_SOURCEID' => self::formatReportValue($reportContent['job']['source']['sourceid'])
                , 'SRC_TYPE' => self::formatReportValue($reportContent['job']['source']['type'])
                , 'SRC_FTPSERVERID' => self::formatReportValue($reportContent['job']['source']['ftpserverid'])
                , 'SRC_NAME' => $srcName
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
                , 'DST_NAME' => $dstName
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
    
    public function getReports($searchDirectory, $searchPattern = '.json') {
        $this->logger->info('AppBundle\Services\SyncReportsService\getReports(): Directory: ' . $searchDirectory);
        $this->logger->info('AppBundle\Services\SyncReportsService\getReports(): Pattern: ' . $searchPattern);
        $reports = array();
        if (is_dir($searchDirectory)) {
            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('*.jso*');
            $finder->depth('== 0');
            $finder->in($searchDirectory);
            foreach ($finder as $file) {
                // If exclude the details file
                if (strpos($file->getRealpath(), $searchPattern) !== false) {
                    $this->logger->info('AppBundle\Services\SyncReportsService\getReports(): Found: ' . $file->getRealpath());
                    array_push($reports, $file->getRealpath());
                    $this->logger->info($file->getRealpath());
                }
            }              
        }      
        return $reports;
    }

    public function getXferStatus($reportFile) {
        $reportDirectory = str_replace("-summary.json", "/", $reportFile);
        $this->logger->info('AppBundle\Services\SyncReportsService\getXferStatus(): Directory: ' . $reportDirectory);
        if (is_dir($reportDirectory)) {
            /* Initial testing revealed that very large directories can actually be very memory intensive, falling back to a simpler unix-based method
            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('*.json.gz');
            $finder->in($reportDirectory);
            return iterator_count($finder);
            */
            // This method is much more archaic, but also much faster to get the count
            //ls -f | wc -l
            $runSystemProcess = new Process('ls -f ' . $reportDirectory . ' | wc -l');
            $runSystemProcess->run();
            $nbFiles = intval($runSystemProcess->getOutput());
            $nbFiles = $nbFiles - 2; // Removed 2 since ls -f also lists . and ..
            return $nbFiles;
        } else {
            return 0;
        }
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
            $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport() - Access to the SRC and DST sources verified');
            
            $userReportsFiles = array();
            $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'queued/'));
            $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'process/'));
            $userReportsFiles = array_merge($userReportsFiles, self::getReports($this->paramDirSyncReports . 'completed/'));
            foreach ($userReportsFiles as $reportFile) {
                $this->logger->info('AppBundle\Services\SyncReportsService\getSyncReportsList() - File: ' . $reportFile);
                $reportFilePathInfo = pathinfo($reportFile);
                if ($inputParams['FILENAME'] === $reportFilePathInfo['basename']) {
                    // We don't actually remove, but move the report to the deleted/ directory
                    $reportDetails = str_replace("-summary.json","-details.json.gz",$reportFile);
                    $reportDir = str_replace("-summary.json","",$reportFile);

                    $fs = new Filesystem();
                    if (!is_dir($this->paramDirSyncReports . 'deleted/')) {$fs->mkdir($this->paramDirSyncReports . 'deleted/', 0700);}

                    if (is_file($reportFile)) {
                        $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport() - About to move file: ' . $reportFile);
                        $reportFilePathInfo = pathinfo($reportFile);
                        rename($reportFile, $this->paramDirSyncReports . 'deleted/' . $reportFilePathInfo['basename']);
                        $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport() - File moved to: ' . $this->paramDirSyncReports . 'deleted/' . $reportFilePathInfo['basename']);
                    }

                    if (is_file($reportDetails)) {
                        $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport() - About to move file: ' . $reportDetails);
                        $reportFileDetailsPathInfo = pathinfo($reportDetails);
                        rename($reportDetails, $this->paramDirSyncReports . 'deleted/' . $reportFileDetailsPathInfo['basename']);
                        $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport() - File moved to: ' . $this->paramDirSyncReports . 'deleted/' . $reportFileDetailsPathInfo['basename']);
                    }

                    if (is_file($reportDir)) {
                        $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport() - About to move directory: ' . $reportDir);
                        $reportDirPathInfo = pathinfo($reportDir);
                        rename($reportDir, $this->paramDirSyncReports . 'deleted/' . $reportDirPathInfo['dirname']);
                        $this->logger->info('AppBundle\Services\SyncReportsService\removeSyncReport() - Directory moved to: ' . $this->paramDirSyncReports . 'deleted/' . $reportDirPathInfo['dirname']);
                    }
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
            if (isset($reportContent['job']['hash'])) {
                $this->logger->info('AppBundle\Services\SyncReportsService\searchSyncReportInQueue() - Found Report Hash: ' . $reportContent['job']['hash']);
                if ( $reportContent['job']['hash'] === $hash) {
                    $reportFound = true;
                }
            }
        }        
        return  $reportFound;        
    }
        
    public function readReportFile($filepath) {
        $this->logger->info('AppBundle\Services\SyncReportsService\readReportFile(): ' . $filepath);
        return json_decode(file_get_contents($filepath), true);    
    }
    
}
