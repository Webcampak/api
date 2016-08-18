<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class StatsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, ConfigurationService $configurationService, PicturesDirectoryService $picturesDirectoryService, $paramDirEtc, $paramDirStats, $paramDirSources) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->configurationService         = $configurationService;
        $this->picturesDirectoryService     = $picturesDirectoryService;
        $this->paramDirEtc                  = $paramDirEtc;
        $this->paramDirStats                = $paramDirStats;
        $this->paramDirSources              = $paramDirSources;
    }

    public function openStatsFile($statsFile) {
        return parse_ini_file($statsFile, TRUE, INI_SCANNER_RAW);
    }

    public function getLatestStatsFile($directory) {
        $this->logger->info('AppBundle\Services\StatsService\getLatestStatsFile()');
        $finder = new Finder();
        $finder->files();
        $finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) { return strcmp($b->getRealpath(), $a->getRealpath()); });
        $finder->files()->name('20*.txt');
        $finder->in($directory);
        foreach ($finder as $file) {
            $this->logger->info('AppBundle\Services\StatsService\getLatestStatsFile() - Looking at file: ' . $file->getFilename());
            return $file;
            break;
        }
    }

    public function parseSystemStatsFile(SplFileInfo $statsFile, $groupBy) {
        $this->logger->info('AppBundle\Services\StatsService\parseSystemStatsFile()');
        $this->logger->info('AppBundle\Services\StatsService\parseSystemStatsFile(): Parsing file: ' . $statsFile->getFilename());
        $statsFileContent = self::openStatsFile($statsFile->getRealpath());
        //Quick and dirty way until better logging is implemented.
        $sourcestats = array();
        $tmpsourcestats = array(
            'BandwidthIn' => 0
            , 'BandwidthOut' => 0
            , 'BandwidthTotal' => 0
            , 'MemoryUsageTotal' => 0
            , 'MemoryUsageUsed' => 0
            , 'MemoryUsageFree' => 0
            , 'DiskUsageTotal' => 0
            , 'DiskUsageUsed' => 0
            , 'DiskUsageFree' => 0
            , 'DiskUsagePercent' => 0
            , 'MemoryUsagePercent' => 0
            , 'CPUUsagePercent' => 0
        );
        $cpt = 0;
        foreach($statsFileContent as $key=>$value) {
            if ($groupBy == 'day') {
                if (isset($value['Timestamp']))         {
                    $tmpsourcestats['Timestamp']           = $value['Timestamp'];
                    $tmpsourcestats['DATE']                = date(DATE_RFC822, $value['Timestamp']);
                }
                if (isset($value['BandwidthIn']))       {$tmpsourcestats['BandwidthIn']         = $tmpsourcestats['BandwidthIn'] + round($value['BandwidthIn']);                     }
                if (isset($value['BandwidthOut']))      {$tmpsourcestats['BandwidthOut']        = $tmpsourcestats['BandwidthOut'] + round($value['BandwidthOut']);                    }
                if (isset($value['BandwidthTotal']))    {$tmpsourcestats['BandwidthTotal']      = $tmpsourcestats['BandwidthTotal'] + round($value['BandwidthTotal']);                  }
                if (isset($value['MemoryUsageTotal']))  {$tmpsourcestats['MemoryUsageTotal']    = $tmpsourcestats['MemoryUsageTotal'] + round($value['MemoryUsageTotal'] / 1024 / 1024);  }
                if (isset($value['MemoryUsageUsed']))   {$tmpsourcestats['MemoryUsageUsed']     = $tmpsourcestats['MemoryUsageUsed'] + round($value['MemoryUsageUsed'] / 1024 / 1024);   }
                if (isset($value['MemoryUsageFree']))   {$tmpsourcestats['MemoryUsageFree']     = $tmpsourcestats['MemoryUsageFree'] + round($value['MemoryUsageFree'] / 1024 / 1024);   }
                if (isset($value['DiskUsageTotal']))    {$tmpsourcestats['DiskUsageTotal']      = $tmpsourcestats['DiskUsageTotal'] + round($value['DiskUsageTotal'] / 1024 / 1024);    }
                if (isset($value['DiskUsageUsed']))     {$tmpsourcestats['DiskUsageUsed']       = $tmpsourcestats['DiskUsageUsed'] + round($value['DiskUsageUsed'] / 1024 / 1024);     }
                if (isset($value['DiskUsageFree']))     {$tmpsourcestats['DiskUsageFree']       = $tmpsourcestats['DiskUsageFree'] + round($value['DiskUsageFree'] / 1024 / 1024);     }
                if (isset($value['DiskUsagePercent']))  {$tmpsourcestats['DiskUsagePercent']    = $tmpsourcestats['DiskUsagePercent'] + round($value['DiskUsagePercent']);	}
                if (isset($value['MemoryUsagePercent'])){$tmpsourcestats['MemoryUsagePercent']  = $tmpsourcestats['MemoryUsagePercent'] + round($value['MemoryUsagePercent']);	}
                if (isset($value['CPUUsagePercent']))   {$tmpsourcestats['CPUUsagePercent'] 	= $tmpsourcestats['CPUUsagePercent'] + round($value['CPUUsagePercent']);	}
                $cpt++;
                if ($cpt == count($statsFileContent)) {
                    $this->logger->info('AppBundle\Services\StatsService\parseSystemStatsFile(): Last record of the array: ' . $cpt . '/' . count($statsFileContent));
                    $tmpsourcestats['BandwidthIn'] = round($tmpsourcestats['BandwidthIn'] / $cpt);
                    $tmpsourcestats['BandwidthOut'] = round($tmpsourcestats['BandwidthOut'] / $cpt);
                    $tmpsourcestats['BandwidthTotal'] = round($tmpsourcestats['BandwidthTotal'] / $cpt);
                    $tmpsourcestats['MemoryUsageTotal'] = round($tmpsourcestats['MemoryUsageTotal'] / $cpt);
                    $tmpsourcestats['MemoryUsageUsed'] = round($tmpsourcestats['MemoryUsageUsed'] / $cpt);
                    $tmpsourcestats['MemoryUsageFree'] = round($tmpsourcestats['MemoryUsageFree'] / $cpt);
                    $tmpsourcestats['DiskUsageTotal'] = round($tmpsourcestats['DiskUsageTotal'] / $cpt);
                    $tmpsourcestats['DiskUsageUsed'] = round($tmpsourcestats['DiskUsageUsed'] / $cpt);
                    $tmpsourcestats['DiskUsageFree'] = round($tmpsourcestats['DiskUsageFree'] / $cpt);
                    $tmpsourcestats['DiskUsagePercent'] = round($tmpsourcestats['DiskUsagePercent'] / $cpt);
                    $tmpsourcestats['MemoryUsagePercent'] = round($tmpsourcestats['MemoryUsagePercent'] / $cpt);
                    $tmpsourcestats['CPUUsagePercent'] = round($tmpsourcestats['CPUUsagePercent'] / $cpt);
                    if (isset($tmpsourcestats['Timestamp']) && $tmpsourcestats['Timestamp'] != "" ) {
                        array_push($sourcestats, $tmpsourcestats);
                    }
                }
            } else {
                $tmpsourcestats = array();
                if (isset($value['Timestamp']))         {
                    $tmpsourcestats['Timestamp']           = $value['Timestamp'];
                    $tmpsourcestats['DATE']                = date(DATE_RFC822, $value['Timestamp']);
                }
                if (isset($value['BandwidthIn']))       {$tmpsourcestats['BandwidthIn']         = round($value['BandwidthIn']);                     }
                if (isset($value['BandwidthOut']))      {$tmpsourcestats['BandwidthOut']        = round($value['BandwidthOut']);                    }
                if (isset($value['BandwidthTotal']))    {$tmpsourcestats['BandwidthTotal']      = round($value['BandwidthTotal']);                  }
                if (isset($value['MemoryUsageTotal']))  {$tmpsourcestats['MemoryUsageTotal']    = round($value['MemoryUsageTotal'] / 1024 / 1024);  }
                if (isset($value['MemoryUsageUsed']))   {$tmpsourcestats['MemoryUsageUsed']     = round($value['MemoryUsageUsed'] / 1024 / 1024);   }
                if (isset($value['MemoryUsageFree']))   {$tmpsourcestats['MemoryUsageFree']     = round($value['MemoryUsageFree'] / 1024 / 1024);   }
                if (isset($value['DiskUsageTotal']))    {$tmpsourcestats['DiskUsageTotal']      = round($value['DiskUsageTotal'] / 1024 / 1024);    }
                if (isset($value['DiskUsageUsed']))     {$tmpsourcestats['DiskUsageUsed']       = round($value['DiskUsageUsed'] / 1024 / 1024);     }
                if (isset($value['DiskUsageFree']))     {$tmpsourcestats['DiskUsageFree']       = round($value['DiskUsageFree'] / 1024 / 1024);     }
                if (isset($value['DiskUsagePercent']))  {$tmpsourcestats['DiskUsagePercent']    = round($value['DiskUsagePercent']);	}
                if (isset($value['MemoryUsagePercent'])){$tmpsourcestats['MemoryUsagePercent']  = round($value['MemoryUsagePercent']);	}
                if (isset($value['CPUUsagePercent']))   {$tmpsourcestats['CPUUsagePercent'] 	= round($value['CPUUsagePercent']);	}
                if (isset($tmpsourcestats['Timestamp']) && $tmpsourcestats['Timestamp'] != "" ) {
                    array_push($sourcestats, $tmpsourcestats);
                }
            }
        }
        $this->logger->info('AppBundle\Services\StatsService\parseSystemStatsFile(): Output: ' . serialize($sourcestats));
        return $sourcestats;
    }

    public function getSystemStats($receivedRange) {
        $this->logger->info('AppBundle\Services\StatsService\getSystemStats()');
        $latestStatsFile = self::getLatestStatsFile($this->paramDirStats);

        if ($receivedRange == "day") {
            $sourcestats = self::parseSystemStatsFile($latestStatsFile, null);
        } else {
            $finder = new Finder();
            $finder->files();
            $groupBy = "day";
            if ($receivedRange == "month") {
                $finder->sortByName();
                $finder->files()->name(substr($latestStatsFile->getFilename(), 0,6) . '*.txt');
            } else if ($receivedRange == "year") {
                $finder->sortByName();
                $finder->files()->name(substr($latestStatsFile->getFilename(), 0,4) . '*.txt');
            }
            $finder->in($this->paramDirStats);
            $sourcestats = array();
            foreach ($finder as $file) {
                $this->logger->info('AppBundle\Services\StatsService\getSystemStats() - Looking at file: ' . $file->getFilename());
                $sourcestats = array_merge($sourcestats, self::parseSystemStatsFile($file, $groupBy));
            }
        }

        $results['results'] = $sourcestats;
        $results['total'] = count($sourcestats);
        return $results;
    }

    /**
     * Gather picture count and size per day for a specific source and possibly limit those results to a specified number of days
     *
     * @param int   $sourceId    The SOURCEID of the source
     * @param int   $days        Number of days to go back in history
     *
     * @return array An array containing size, count and dates
     */        
    public function getSourcesPicturesCountSize($sourceId, $days = null) {
        $this->logger->info('AppBundle\Services\StatsService\getSourcesPicturesCountSize()');
        $firstDay = $this->picturesDirectoryService->getFirstPictureDay($sourceId);
        $lastDay = $this->picturesDirectoryService->getLatestPictureDay($sourceId);

        $this->logger->info('AppBundle\Services\StatsService\getSourcesPicturesCountSize(): First Day: ' . $firstDay . ' Last Day: ' . $lastDay);

        $dateRange = $this->picturesDirectoryService->listDaysBetweenTwoDates($firstDay, $lastDay);
        if ($days !== null) {
            $dateRange = array_reverse($dateRange);
            $dateRange = array_slice($dateRange, 0, 15);
        }

        $pictureCountArray = array();
        foreach ($dateRange as $date) {
            $pictureCount = $this->picturesDirectoryService->countPicturesForDay($sourceId, $date);
            $pictureSize = $this->picturesDirectoryService->sizePicturesForDay($sourceId, $date);
            $this->logger->info('AppBundle\Services\StatsService\getSourcesPicturesCountSize() - Day: ' . $date . ' has ' . $pictureCount . ' pictures, total size: ' . $pictureSize);

            $currentDate = mktime(1,0,0,substr($date,4,2),substr($date,6,2),substr($date,0,4));

            array_push($pictureCountArray, array(
                'DATE' => date(DATE_ATOM, $currentDate)
                , 'COUNT' => $pictureCount
                , 'SIZE' => $pictureSize
            ));
        }
        
        return $pictureCountArray;
    }

    /**
     * Parse stats files and return an array containing dates and sizes
     *
     * @param int   $statsFile    Filename to be analyzed
     *
     * @return array An array containing size and dates
     */      
    private function parseSourcesStatsFile(SplFileInfo $statsFile) {
        $this->logger->info('AppBundle\Services\StatsService\parseSourcesStatsFile()');
        $this->logger->info('AppBundle\Services\StatsService\parseSourcesStatsFile(): Parsing file: ' . $statsFile->getFilename());
        $statsFileContent = self::openStatsFile($statsFile->getRealpath());
        //Quick and dirty way until better logging is implemented.
        $sourcestats = array();
        $tmpsourcestats = array(
            'SIZE' => 0
        );
        $cpt = 0;
        foreach($statsFileContent as $key=>$value) {
            if (isset($value['Timestamp'])) {$tmpsourcestats['DATE']    = date(DATE_ATOM, $value['Timestamp']);}
            if (isset($value['GlobalSize'])){$tmpsourcestats['SIZE']    = $tmpsourcestats['SIZE'] + $value['GlobalSize'];}
            $cpt++;
            if ($cpt == count($statsFileContent)) {
                $this->logger->info('AppBundle\Services\StatsService\parseSourcesStatsFile(): Last record of the array: ' . $cpt . '/' . count($statsFileContent));
                $tmpsourcestats['SIZE'] = round($tmpsourcestats['SIZE'] / $cpt);
                if (isset($tmpsourcestats['DATE']) && $tmpsourcestats['DATE'] != "" ) {
                    array_push($sourcestats, $tmpsourcestats);
                }
            }
        }
        $this->logger->info('AppBundle\Services\StatsService\parseSourcesStatsFile(): Output: ' . serialize($sourcestats));
        return $sourcestats;
    }

    /**
     * Gather disk usage stats for a specific source and possibly limit those results to a specified number of days
     *
     * @param int   $sourceId    The SOURCEID of the source
     * @param int   $days        Number of days to go back in history
     *
     * @return array An array containing size and dates
     */    
    public function getSourcesDiskUsage($sourceId, $days = null) {
        $this->logger->info('AppBundle\Services\StatsService\getSourcesDiskUsage()');

        $finder = new Finder();
        $finder->files();
        if ($days !== null) {$finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) { return strcmp($b->getRealpath(), $a->getRealpath()); });} 
        else {$finder->sortByName();}        
        $finder->files()->name('20*.txt');
        $finder->in($this->paramDirSources . 'source' . $sourceId . '/resources/stats/');
        $sourcestats = array();
        $filecpt = 0;
        foreach ($finder as $file) {
            if ($days !== null && $filecpt > $days) {
                break;
            } else {
                $this->logger->info('AppBundle\Services\StatsService\getSystemStats() - Looking at file: ' . $file->getFilename());
                $sourcestats = array_merge($sourcestats, self::parseSourcesStatsFile($file));
                $filecpt++;                
            }
        }
        return $sourcestats;
    }

}
