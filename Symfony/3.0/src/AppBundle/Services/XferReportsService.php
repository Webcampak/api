<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use \DateTime;

class XferReportsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, UserService $userService, ConfigurationService $configurationService, $paramDirXfer, $paramDirSources, $paramDirEtc) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->userService                  = $userService;        
        $this->configurationService         = $configurationService;        
        $this->paramDirXfer                 = $paramDirXfer;
        $this->paramDirXferThreads          = $paramDirXfer . 'threads/';
        $this->paramDirXferQueued           = $paramDirXfer . 'queued/';
        $this->paramDirSources              = $paramDirSources;
        $this->paramDirEtc                  = $paramDirEtc;
    }

    public function getXferReportsList() {
        $this->logger->info('AppBundle\Services\XferReportsService\getXferReportsList()');
        
        $threadsList = array();
        $allThreads = self::getThreads();
        foreach ($allThreads as $currentThread) {
            $threadContent = self::readThreadFile($currentThread);
            if (!isset($threadContent['date_updated'])) {$threadContent['date_updated'] = null;}
            if (!isset($threadContent['pid'])) {$threadContent['pid'] = null;}
            if ($threadContent['last_job'] !== null) {
                $jobStarted = $threadContent['last_job']['date_started'];
                $jobCompleted = $threadContent['last_job']['date_completed'];
                $jobDirection = $threadContent['last_job']['direction'];
                $jobSize = $threadContent['last_job']['bytes'];
                $jobSeconds = $threadContent['last_job']['seconds'];
                $jobRate = round($threadContent['last_job']['bytes'] / $threadContent['last_job']['seconds']);
            } else {
                $jobStarted = null;
                $jobCompleted = null;
                $jobDirection = null;
                $jobSize = null;
                $jobSeconds = null;
                $jobRate = null;      
            }
            array_push($threadsList, array(
                'UUID' => $threadContent['uuid']
                , 'DATE_UPDATED' => self::formatReportValue($threadContent['date_updated'])
                , 'DATE_CREATED' => self::formatReportValue($threadContent['date_created'])
                , 'PID' => $threadContent['pid']
                , 'QUEUE' => self::getThreadQueueCount($threadContent['uuid'])
                , 'JOB_STARTED' => self::formatReportValue($jobStarted)
                , 'JOB_COMPLETED' => self::formatReportValue($jobCompleted)
                , 'JOB_DIRECTION' => self::formatReportValue($jobDirection)
                , 'JOB_SIZE' => self::formatReportValue($jobSize)
                , 'JOB_SECONDS' => self::formatReportValue($jobSeconds)
                , 'JOB_RATE' => self::formatReportValue($jobRate)                    
            ));
                        
        }
        return $threadsList;
    }

    public function formatReportValue($reportValue) {
        if (isset($reportValue)){return $reportValue;} 
        else {return '';}
    }    
    
    public function getThreads() {
        $this->logger->info('AppBundle\Services\XferReportsService\getThreads()');        
        $threads = array();
        if (is_dir($this->paramDirXferThreads)) {
            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('*.json');
            $finder->depth('== 0');
            $finder->in($this->paramDirXferThreads);
            foreach ($finder as $file) {   
                array_push($threads, $file->getRealpath());
                $this->logger->info($file->getRealpath());                        
            }              
        }      
        return $threads;
    }     

    public function getThreadQueueCount($uuid) {
        $this->logger->info('AppBundle\Services\XferReportsService\getThreadQueue()');        
        $fileCount = 0;
        if (is_dir($this->paramDirXferThreads . $uuid . '/')) {
            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('*.json');
            $finder->depth('== 0');
            $finder->in($this->paramDirXferThreads . $uuid . '/');
            $fileCount = iterator_count($finder);           
        }      
        return $fileCount;
    } 
    public function getOverallQueueCount() {
        $this->logger->info('AppBundle\Services\XferReportsService\getOverallQueueCount()');        
        $fileCount = 0;
        if (is_dir($this->paramDirXferQueued)) {
            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('*.json');
            $finder->in($this->paramDirXferQueued);
            $fileCount = iterator_count($finder);           
        }      
        return $fileCount;
    }     
      
    public function readThreadFile($filepath) {
        $this->logger->info('AppBundle\Services\XferReportsService\readThreadFile(): ' . $filepath);
        return json_decode(file_get_contents($filepath), true);    
    }
    
}
