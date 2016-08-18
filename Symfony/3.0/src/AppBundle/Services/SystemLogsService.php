<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;

class SystemLogsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, $paramDirLogs) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->paramDirLogs                 = $paramDirLogs;
    }

    public function getLogFile($receivedSourceid = null, $logtype) {
        $this->logger->info('AppBundle\Services\SystemLogsService\getLogFile() - Source: ' . $receivedSourceid .  ' - Log Type: ' . $logtype);
        if (intval($receivedSourceid) > 0) {
            if ($logtype == 'capture')              {$logfile = 'capture-' . $receivedSourceid . '.log';}
            else if ($logtype == 'customvideos')    {$logfile = 'cronlog-' . $receivedSourceid . '-customvid';}
            else if ($logtype == 'videos')          {$logfile = 'cronlog-' . $receivedSourceid . '-dailyvid';}
            else if ($logtype == 'posprod')         {$logfile = 'cronlog-' . $receivedSourceid . '-post';}

            /*
            capture-1.log
            cronlog-1-customvid
            cronlog-1-dailyvid
            cronlog-1-post
            edit-source-10-2015-06-14.log
            */
            $outputlog = array();
            if ($logtype != 'configuration') {
                $this->logger->info('AppBundle\Services\SystemLogsService\getLogFile() - Accessing file: ' . $this->paramDirLogs . $logfile);
                if (is_file($this->paramDirLogs . $logfile)) {
                    $content = file_get_contents($this->paramDirLogs . $logfile);
                    $convert = explode("\n", $content);
                    $linesCount = count($convert);
                    for ($i=0;$i<$linesCount;$i++) {
                        $outputlogline = array();
                        $outputlogline['LINE'] = $i + 1;
                        $outputlogline['CONTENT'] = $convert[$i];
                        if ($convert[$i] != "") {
                            array_push($outputlog, $outputlogline);
                        }
                    }                    
                }
            } else {
                $this->logger->info('AppBundle\Services\SystemLogsService\getLogFile() - Request some configuration logs');
                $maxLines = 200;
                $currentLines = 0;
                $finder = new Finder();
                $finder->files();
                $finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) { return strcmp($b->getRealpath(), $a->getRealpath()); });
                $finder->files()->name('edit-source-' . $receivedSourceid . '-20*.log');
                $finder->in($this->paramDirLogs);
                foreach ($finder as $file) {
                    $this->logger->info('AppBundle\Services\SystemLogsService\getLogFile() - Looking at file: ' . $file->getFilename());
                    $logFileContent = file($this->paramDirLogs . $file->getFilename());
                    $logFileContent = array_reverse($logFileContent);
                    foreach($logFileContent as $f){
                        //{"DATE":"Sun, 14 Jun 15 14:41:32 +0000","USERNAME":"root","IP":"127.0.0.1","TYPE":"CONFIG","FILE":"\/home\/francois\/webcampak\/etc\/config-source1.cfg","PARAMETER":"cfgcroncapturevalue","OLD":"5","NEW":"6"}
                        $logLine = json_decode($f, true);
                        if ($logLine !== null) {
                            $currentLines++;
                            array_push($outputlog, array(
                                'LINE' => $currentLines
                                , 'DATE' => $logLine['DATE']
                                , 'USERNAME' => $logLine['USERNAME']
                                , 'FILE' => basename($logLine['FILE'])
                                , 'PARAMETER' => $logLine['PARAMETER']
                                , 'OLD' => $logLine['OLD']
                                , 'NEW' => $logLine['NEW']
                            ));
                        }
                        if ($currentLines >= $maxLines) {break;}
                    }
                    if ($currentLines >= $maxLines) {break;}
                }

            }
            $results['results'] = $outputlog;
            $results['total'] = count($outputlog);
            return $results;
        } else {
            $results = array("success" => false, "title" => "Source Access", "message" => "Unable to access the source");                                        
        }
    }

}
