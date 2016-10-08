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

use Symfony\Component\Process\Process;

class RunSyncReportsCommand extends ContainerAwareCommand
{
    private $output;
    private $reportContent;
    private $duParsedOutput;
    private $serverTimezone;
    
    protected function configure() {
        $this
            ->setName('wpak:runsyncreports')
            ->setDescription('Process the sync report queue')
            ->addArgument('sleep', InputArgument::OPTIONAL, 'Sleep before starting to process the queue (seconds)')                
        ;
    }

    function log($level, $message) {
        $this->output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }
        
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        self::log('info', '--------------------------------------------------------');
        self::log('info', '|           PROCESS THE SYNC REPORT QUEUE              |');
        self::log('info', '--------------------------------------------------------');

        //Fake the authentication mechanism and act as root
        $searchRootUserEntity = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Users')->findOneByUsername('root');                        
        $token = new UsernamePasswordToken($searchRootUserEntity, null, "secured_area", $searchRootUserEntity->getRoles());            
        $this->getContainer()->get("security.token_storage")->setToken($token);          
        
        $sleepTime = $input->getArgument('sleep');
        if ($sleepTime) {
            self::log('info', 'Program will sleep for ' . $sleepTime . ' seconds');
            sleep($sleepTime);
        }        

        $this->serverTimezone = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_config') . 'config-general.cfg', 'cfgservertimezone');
        
        $fs = new Filesystem();        
        $reportDir = $this->getContainer()->getParameter('dir_sync-reports');        
        $reportFiles = $this->getContainer()->get('app.svc.syncreports')->getReports($reportDir . 'queued/');
        foreach ($reportFiles as $currentReportFile) {
            self::log('info', 'RunSyncReportsCommand.php\execute() - Looking at file: ' . $currentReportFile);  
            $reportFilePathInfo = pathinfo($currentReportFile);
            $currentFileDir = $reportDir . 'process/';
            $currentFileName = $reportFilePathInfo['basename'];
            
            $this->reportContent = $this->getContainer()->get('app.svc.syncreports')->readReportFile($currentReportFile);
            $this->reportContent['job']['status'] = 'process';

            //1- Move the file to the process directory, any file remaining here for too long can be considered failed
            $fs->dumpFile($currentFileDir . $currentFileName, json_encode($this->reportContent));
            $fs->remove($currentReportFile); 

            $currentDate = new \DateTime('now', new \DateTimeZone($this->serverTimezone));
            $this->reportContent['job']['date_start'] = $currentDate->format('c');            
                
            self::processLog($currentFileDir . $currentFileName, 'Report file moved to processing directory');
            self::processReport($currentFileDir, $currentFileName);

            $currentDate = new \DateTime('now', new \DateTimeZone($this->serverTimezone));
            $this->reportContent['job']['status'] = 'completed';            
            $this->reportContent['job']['date_completed'] = $currentDate->format('c');               
            
            //2- Check if some files need to be transferred
            if ($this->reportContent['job']['xfer'] === true) {
                self::processLog($currentFileDir . $currentFileName, 'Preparing xfer queue');                
                self::queueXferFiles($currentFileDir, $currentFileName);                
            }
            
            //3- Once done, move the file to the completed directory
            $fs->dumpFile($this->getContainer()->getParameter('dir_sources') . 'source' . $this->reportContent['job']['source']['sourceid'] . '/resources/sync-reports/' . $currentFileName, json_encode($this->reportContent, JSON_PRETTY_PRINT));
            $fs->remove($currentFileDir . $currentFileName); 
            self::log('info', 'RunSyncReportsCommand.php\execute() - Processing completed for: ' . $currentReportFile);              
        } 
        self::log('info', 'RunSyncReportsCommand.php\execute() - Finished processing queue');          
                        
    }

    function processLog($file, $logMessage) {
        self::log('info', 'AlertsCommand.php\processLog() - ' . $logMessage);

        $fs = new Filesystem();                
        $currentDate = new \DateTime('now', new \DateTimeZone($this->serverTimezone));
        
        if (!isset($this->reportContent['job']['logs']) || count($this->reportContent['job']['logs']) == 0) {$this->reportContent['job']['logs'] = array();}
        array_push($this->reportContent['job']['logs'], array(
            'date' => $currentDate->format('c')
            , 'message' => $logMessage
        ));
        
        $fs->dumpFile($file, json_encode($this->reportContent, JSON_PRETTY_PRINT));
    }
    
    function queueXferFiles($currentFileDir, $currentFileName) {
        self::log('info', 'AlertsCommand.php\queueXferFiles()');
        if (isset($this->reportContent['result']['destination']['missing']['list']) && count($this->reportContent['result']['destination']['missing']['list']) > 0) {
            self::log('info', 'AlertsCommand.php\queueXferFiles(): Number of files to queue for transfer: ' . count($this->reportContent['result']['destination']['missing']['list']));
            foreach($this->reportContent['result']['destination']['missing']['list'] as $currentMissingFile) {
                //1- Identify filename for the queued file
                //Filename is built as follow:
                // 2016040112-S2-MD5.json (YYYYMMDDHH-Source ID-MD5
                $missingPictureFilename = pathinfo($currentMissingFile['path']);
                $missingPictureFilename = $missingPictureFilename['basename'];

                $filenameMd5 = md5('S' 
                        . $this->reportContent['job']['source']['sourceid'] . '-'
                        . $this->reportContent['job']['source']['type'] . '-'
                        . $this->reportContent['job']['source']['ftpserverid'] . '-'
                        . $this->reportContent['job']['destination']['sourceid'] . '-'
                        . $this->reportContent['job']['destination']['type'] . '-'
                        . $this->reportContent['job']['destination']['ftpserverid'] . '-'                        
                        . $currentMissingFile['path']                       
                    );
                $currentMissingFilename = substr($missingPictureFilename, 0,12) . '-' . $this->reportContent['job']['source']['sourceid'] . '-' . $filenameMd5 . '.json';
                self::log('info', 'AlertsCommand.php\queueXferFiles(): Currrent Filename: ' . $currentMissingFilename);
                
                //2- Check if file exists in queue
                if (!is_file($this->getContainer()->getParameter('dir_xfer') . 'queued/' . substr($currentMissingFilename, 0,8) . '/' . $currentMissingFilename)) {
                    #If transfer type is ftp, calculate the server md5, composed of remote host and username
                    if ($this->reportContent['job']['source']['type'] === 'ftp') {
                        $ftpConfigFile = $this->getContainer()->getParameter('dir_etc') . 'config-source' . $this->reportContent['job']['source']['sourceid'] . '-ftpservers.cfg';
                        $ftpServersFromConfigFile = $this->getContainer()->get('app.svc.ftp')->getServersFromConfigFile($ftpConfigFile);
                        $this->reportContent['job']['source']['ftpserverhash'] = $this->getContainer()->get('app.svc.ftp')->calculateFTPServerHash($ftpServersFromConfigFile, $this->reportContent['job']['source']['ftpserverid']);
                    } else {
                        $this->reportContent['job']['source']['ftpserverhash'] = null;
                    }
                    if ($this->reportContent['job']['destination']['type'] === 'ftp') {
                        $ftpConfigFile = $this->getContainer()->getParameter('dir_etc') . 'config-source' . $this->reportContent['job']['destination']['sourceid'] . '-ftpservers.cfg';
                        $ftpServersFromConfigFile = $this->getContainer()->get('app.svc.ftp')->getServersFromConfigFile($ftpConfigFile);
                        $this->reportContent['job']['destination']['ftpserverhash'] = $this->getContainer()->get('app.svc.ftp')->calculateFTPServerHash($ftpServersFromConfigFile, $this->reportContent['job']['destination']['ftpserverid']);
                    }  else {
                        $this->reportContent['job']['destination']['ftpserverhash'] = null;
                    }
                    $xferContent = array();
                    $xferContent['job'] = array();
                    $xferContent['job']['status'] = 'queued';
                    #$xferContent['job']['path'] = $currentMissingFile['path'];                   
                    $xferContent['job']['source'] = $this->reportContent['job']['source'];
                    $xferContent['job']['source']['filepath'] = $currentMissingFile['path'];
                    $xferContent['job']['destination'] = $this->reportContent['job']['destination'];
                    $xferContent['job']['destination']['filepath'] = $currentMissingFile['path'];                    
                    $xferContent['job']['hash'] = $filenameMd5;
                    $xferContent['job']['sync-report']['filename'] = $currentFileName;
                    $xferContent['job']['sync-report']['hash'] = $this->reportContent['job']['hash'];
                    $currentDate = new \DateTime('now', new \DateTimeZone($this->serverTimezone));      
                    $xferContent['job']['date_queued'] = $currentDate->format('c');
                    $xferContent['job']['date_start'] = '';
                    $xferContent['job']['date_completed'] = '';                    
                    $xferContent['logs'] = array();   
                    $fs = new Filesystem();
                    $fs->dumpFile($this->getContainer()->getParameter('dir_xfer') . 'queued/' . substr($currentMissingFilename, 0,8) . '/' . $currentMissingFilename, json_encode($xferContent, JSON_FORCE_OBJECT));        
                    self::log('info', 'AlertsCommand.php\queueXferFiles(): Added file to XFER queue');                    
                } else {
                    self::log('info', 'AlertsCommand.php\queueXferFiles(): File already exists, skipping...');                    
                }                
            }            
        } else {
            self::log('info', 'AlertsCommand.php\queueXferFiles(): No files to queue for transfer');
            
        }
        
    }
    
    function processReport($currentFileDir, $currentFileName) {
        self::log('info', 'AlertsCommand.php\processReport()');
        //$fs = new Filesystem();
        
        //1- Add source files
        if ($this->reportContent['job']['source']['type'] === 'filesystem') {
            $parsedDu = self::runFilesystemDu($this->reportContent['job']['source']['sourceid'], 'pictures/');            
        } else if ($this->reportContent['job']['source']['type'] === 'ftp') {
            $parsedDu = self::runFtpDu($this->reportContent['job']['source']['sourceid'], $this->reportContent['job']['source']['ftpserverid'], 'pictures/');                        
        }
        if ($parsedDu !== false) {
            $this->reportContent['result']['source']['files'] = $parsedDu;
            self::processLog($currentFileDir . $currentFileName, 'Scanned source directory for pictures');            
        }
        
        //2- Add destination files
        if ($this->reportContent['job']['destination']['type'] === 'filesystem') {
            $parsedDu = self::runFilesystemDu($this->reportContent['job']['destination']['sourceid'], 'pictures/');            
        } else if ($this->reportContent['job']['destination']['type'] === 'ftp') {
            $parsedDu = self::runFtpDu($this->reportContent['job']['destination']['sourceid'], $this->reportContent['job']['destination']['ftpserverid'], 'pictures/');                        
        }        
//        $parsedDu = self::runFilesystemDu($this->reportContent['job']['destination']['path']);
        if ($parsedDu !== false) {
            $this->reportContent['result']['destination']['files'] = $parsedDu;
            self::processLog($currentFileDir . $currentFileName, 'Scanned destination directory for pictures');            
        }
        
        //3- Calculate diff
        $this->reportContent['result']['destination']['missing']['list'] = array_diff_key($this->reportContent['result']['source']['files']['list'], $this->reportContent['result']['destination']['files']['list']);
        $dstSummary = self::summarizeFileList($this->reportContent['result']['destination']['missing']['list']);
        $this->reportContent['result']['destination']['missing']['count'] = $dstSummary['count'];
        $this->reportContent['result']['destination']['missing']['size'] = $dstSummary['size'];        
        self::processLog($currentFileDir . $currentFileName, 'Calculated diff between source and destination (missing pictures at source)');            

        $this->reportContent['result']['source']['missing']['list'] = array_diff_key($this->reportContent['result']['destination']['files']['list'], $this->reportContent['result']['source']['files']['list']);
        $srcSummary = self::summarizeFileList($this->reportContent['result']['source']['missing']['list']);
        $this->reportContent['result']['source']['missing']['count'] = $srcSummary['count'];
        $this->reportContent['result']['source']['missing']['size'] = $srcSummary['size'];        
        self::processLog($currentFileDir . $currentFileName, 'Calculated diff between destination and source (missing pictures at destination');            

        $this->reportContent['result']['intersect']['list'] = array_intersect_key($this->reportContent['result']['source']['files']['list'], $this->reportContent['result']['destination']['files']['list']);
        $itrSummary = self::summarizeFileList($this->reportContent['result']['intersect']['list']);
        $this->reportContent['result']['intersect']['count'] = $itrSummary['count'];
        $this->reportContent['result']['intersect']['size'] = $itrSummary['size'];        
        self::processLog($currentFileDir . $currentFileName, 'Calculated intersection between destination and source');            
                
    }

    function summarizeFileList($fileList) {
        self::log('info', 'summarizeFileList');
        $summary = array('count' => array('jpg' => 0, 'raw' => 0, 'total' => 0), 'size' => array('jpg' => 0, 'raw' => 0, 'total' => 0));        
        foreach($fileList as $currentFile) {
            $summary['count']['total'] = $summary['count']['total'] + 1;
            if ($currentFile['type'] === 'jpg') {$summary['count']['jpg'] = $summary['count']['jpg']+1;}
            else {$summary['count']['raw'] = $summary['count']['raw']+1;}
            
            $summary['size']['total'] = $summary['size']['total'] + $currentFile['size'];    
            if ($currentFile['type'] === 'jpg') {$summary['size']['jpg'] = $summary['size']['jpg']+$currentFile['size'];}
            else {$summary['size']['raw'] = $summary['size']['raw']+$currentFile['size'];}
            
        }
        return $summary;
    }

    function runFtpDu($sourceId, $serverId, $duDirectory) {
        self::log('info', 'runFtpDu(): Running du -a -b via lftp');
        $ftpConfigFile = $this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceId . '-ftpservers.cfg';
        $ftpServersFromConfigFile = $this->getContainer()->get('app.svc.ftp')->getServersFromConfigFile($ftpConfigFile);
        $ftpServer = $this->getContainer()->get('app.svc.ftp')->getFtpServerbyId($ftpServersFromConfigFile, $serverId);
        $this->ftpServer = $ftpServer;
        //$this->ftpDuDirectory = $duDirectory;
        self::log('info', 'runFtpDu(): FTP Server: ' . $ftpServer['NAME']);
        $this->duParsedOutput = array('list' => array(), 'count' => array('jpg' => 0, 'raw' => 0, 'total' => 0), 'size' => array('jpg' => 0, 'raw' => 0, 'total' => 0));
        //$runSystemProcess = new Process('lftp -u ' . $ftpServer['USERNAME'] . ':' . $ftpServer['PASSWORD'] . ' ' . $ftpServer['HOST'] . ':' . $ftpServer['DIRECTORY'] . $duDirectory . ' -e "du -b -a;exit"');
        $runSystemProcess = new Process('lftp -u ' . $ftpServer['USERNAME'] . ':' . $ftpServer['PASSWORD'] . ' ' . $ftpServer['HOST'] . ':' . $ftpServer['DIRECTORY'] . ' -e "du -b -a;exit"');
        $runSystemProcess->setTimeout(120000);
        $runSystemProcess->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                self::log('info', 'runFtpDu(): Error: ' . $buffer);                
            } else {
                $processOutputLines = explode("\n", $buffer);
                foreach($processOutputLines as $processLine) {
                    $duParsedLine = self::parseDuLine($processLine, './');
                    if ($duParsedLine !== false && intval($duParsedLine['size']) > 0 && ($duParsedLine['type'] === 'jpg' || $duParsedLine['type'] === 'raw')) {
                        $duParsedLine['path'] = substr($duParsedLine['path'], 1); #Hack: When running ftp du, remove first characted of the path, which is typically a /
                        //$duParsedLine['path'] = $this->ftpDuDirectory . $duParsedLine['path'];
                        //self::log('info', 'runFtpDu(): Filepath: ' . $duParsedLine['path']);
                        $currentMd5 = md5($duParsedLine['size'] . $duParsedLine['type'] . $duParsedLine['path']);
                        $this->duParsedOutput['list'][$currentMd5] = $duParsedLine;
                        $this->duParsedOutput['count']['total'] = $this->duParsedOutput['count']['total']+1;
                        if ($duParsedLine['type'] === 'jpg') {$this->duParsedOutput['count']['jpg'] = $this->duParsedOutput['count']['jpg']+1;}
                        else {$this->duParsedOutput['count']['raw'] = $this->duParsedOutput['count']['raw']+1;}
                        $this->duParsedOutput['size']['total'] = $this->duParsedOutput['size']['total'] + $duParsedLine['size'];
                        if ($duParsedLine['type'] === 'jpg') {$this->duParsedOutput['size']['jpg'] = $this->duParsedOutput['size']['jpg']+$duParsedLine['size'];}
                        else {$this->duParsedOutput['size']['raw'] = $this->duParsedOutput['size']['raw']+$duParsedLine['size'];}
                    }            
                }
            }
        });
        if (!$runSystemProcess->isSuccessful()) {
            self::log('error', 'Unable to perform action');
            return false;
        } 
        return $this->duParsedOutput;
    }    
    
    function runFilesystemDu($sourceId, $duDirectory) {
        $fullDuDirectoryPath = $this->getContainer()->getParameter('dir_sources') . 'source' . intval($sourceId) . '/' . $duDirectory;
        $sourcePath = $this->getContainer()->getParameter('dir_sources') . 'source' . intval($sourceId) . '/';
        self::log('info', 'Running command: du -b -a ' . $fullDuDirectoryPath);
        $duParsedOutput = array('list' => array(), 'count' => array('jpg' => 0, 'raw' => 0, 'total' => 0), 'size' => array('jpg' => 0, 'raw' => 0, 'total' => 0));
        $runSystemProcess = new Process('du -b -a ' . $fullDuDirectoryPath);
        $runSystemProcess->run();
        $processOutputLines = explode("\n", $runSystemProcess->getOutput());
        foreach($processOutputLines as $processLine) {
            //$duParsedLine = self::parseDuLine($processLine, $fullDuDirectoryPath);
            $duParsedLine = self::parseDuLine($processLine, $sourcePath);
            if ($duParsedLine !== false && intval($duParsedLine['size']) > 0 && ($duParsedLine['type'] === 'jpg' || $duParsedLine['type'] === 'raw')) {
                $currentMd5 = md5($duParsedLine['size'] . $duParsedLine['type'] . $duParsedLine['path']);
                $duParsedOutput['list'][$currentMd5] = $duParsedLine;
                $duParsedOutput['count']['total'] = $duParsedOutput['count']['total']+1;
                if ($duParsedLine['type'] === 'jpg') {$duParsedOutput['count']['jpg'] = $duParsedOutput['count']['jpg']+1;}
                else {$duParsedOutput['count']['raw'] = $duParsedOutput['count']['raw']+1;}
                $duParsedOutput['size']['total'] = $duParsedOutput['size']['total'] + $duParsedLine['size'];
                if ($duParsedLine['type'] === 'jpg') {$duParsedOutput['size']['jpg'] = $duParsedOutput['size']['jpg']+$duParsedLine['size'];}
                else {$duParsedOutput['size']['raw'] = $duParsedOutput['size']['raw']+$duParsedLine['size'];}
            }            
        }
        if (!$runSystemProcess->isSuccessful()) {
            self::log('error', 'Unable to perform action');
            return false;
        } 
        return $duParsedOutput;
    }
    
    function parseDuLine($duLine, $duDirectory) {
        self::log('info', 'Du line: ' . $duLine);        
        $reFileSize='(\\d+)';               # Integer Number 1
        $reSpace='.*?';                     # Non-greedy match on filler
        $rePath='((?:\\/[\\w\\.\\-]+)+)';   # Unix Path 1
        $parsedLine = array();
        if (preg_match_all ("/".$reFileSize.$reSpace.$rePath."/is", $duLine, $matches)) {
            $parsedLine['size'] = $matches[1][0];
            $parsedLine['path'] = str_replace($duDirectory, "", $matches[2][0]);
            $parsedLine['type'] = pathinfo($parsedLine['path'], PATHINFO_EXTENSION);
            return $parsedLine;
        } else {
            return false;
        }
        
    }
}

