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

    public function getFileList($receivedRange) {
        $this->logger->info('AppBundle\Services\StatsService\getFileList(): Range: ' . $receivedRange);
        $fileList = array();
        $scanDirectory = $this->paramDirStats;
        if ($receivedRange !== 'recent') {
            $scanDirectory = $this->paramDirStats . "consolidated/";
        }
        $this->logger->info('AppBundle\Services\StatsService\getFileList(): Scanning directory: ' . $scanDirectory);
        $finder = new Finder();
        $finder->depth(0);
        $finder->files();
        $finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) { return strcmp($b->getRealpath(), $a->getRealpath()); });
        $finder->files()->name('20*.jsonl');
        $finder->in($scanDirectory);
        foreach ($finder as $file) {
            if ($receivedRange == 'recent') {
                // We keep all files, sorting is done later on
                $this->logger->info('AppBundle\Services\StatsService\getFileList() - Adding file: ' . $file->getFilename());
                array_push($fileList, $file);
            } elseif ($receivedRange == 'hours' && strlen($file->getFilename()) === 14) {
                // If hours, we only keep daily files (20160830.jsonl)
                $this->logger->info('AppBundle\Services\StatsService\getFileList() - Adding file: ' . $file->getFilename());
                array_push($fileList, $file);
            } elseif ($receivedRange == 'days' && strlen($file->getFilename()) === 12) {
                // If hours, we only keep daily files (201608.jsonl)
                $this->logger->info('AppBundle\Services\StatsService\getFileList() - Adding file: ' . $file->getFilename());
                array_push($fileList, $file);
            } elseif ($receivedRange == 'months' && strlen($file->getFilename()) === 10) {
                // If hours, we only keep daily files (2016.jsonl)
                $this->logger->info('AppBundle\Services\StatsService\getFileList() - Adding file: ' . $file->getFilename());
                array_push($fileList, $file);
            } else {
                $this->logger->info('AppBundle\Services\StatsService\getFileList() - File outside of requested range: ' . $file->getFilename());
            }
        }
        return $fileList;
    }

    public function getFileData($fileList) {
        // Take a list of files and extract the first 10 data records
        $maxRecords = 100;
        $this->logger->info('AppBundle\Services\StatsService\getFileData()');
        $fileData = array();
        $recordCount = 0;
        foreach ($fileList as $currentFile) {
            $this->logger->info('AppBundle\Services\StatsService\getFileData() - Going through file: ' . $currentFile);
            if ($recordCount >= $maxRecords) {break;}
            $fileContent = array_reverse(file($currentFile));
            foreach ($fileContent as $currentFileLine) {
                $this->logger->info('AppBundle\Services\StatsService\getFileData() - Current Line: ' . $currentFileLine);
                if ($recordCount >= $maxRecords) {break;}
                $currentRecord = json_decode($currentFileLine, true);
                // If recent, sample JSON:
                //{"date": "2016-09-01T16:25:01.516383+02:00", "BandwidthIn": "0.12", "BandwidthOut": "0.12", "BandwidthTotal": "0.24", "MemoryUsageTotal": "6108524544", "MemoryUsageUsed": "2942509056", "MemoryUsageFree": "3166015488", "MemoryUsagePercent": "28.1", "DiskUsageTotal": "61312446464", "DiskUsageUsed": "23133470720", "DiskUsageFree": "35040800768", "DiskUsagePercent": "37.7", "CPUUsagePercent": "18.5"}
                // If not recent, sample JSON:
                //{"date": "2016-08-30T19:00:00+02:00", "BandwidthIn": {"min": 0, "max": 0, "avg": 0}, "BandwidthOut": {"min": 0, "max": 0, "avg": 0}, "BandwidthTotal": {"min": 0, "max": 0, "avg": 0}, "MemoryUsageTotal": {"min": 6108532736, "max": 6108532736, "avg": 6108532736}, "MemoryUsageUsed": {"min": 5697355776, "max": 5717053440, "avg": 5708737536}, "MemoryUsageFree": {"min": 391479296, "max": 411176960, "avg": 399795200}, "MemoryUsagePercent": {"min": 43, "max": 44, "avg": 43}, "DiskUsageTotal": {"min": 61312446464, "max": 61312446464, "avg": 61312446464}, "DiskUsageUsed": {"min": 20793053184, "max": 21026095104, "avg": 20910688256}, "DiskUsageFree": {"min": 37148176384, "max": 37381218304, "avg": 37263583232}, "DiskUsagePercent": {"min": 33, "max": 34, "avg": 34}, "CPUUsagePercent": {"min": 1, "max": 2, "avg": 1}}
                if (is_array($currentRecord['BandwidthIn'])) {$currentRecord['BandwidthIn'] = $currentRecord['BandwidthIn']['avg'];}
                if (is_array($currentRecord['BandwidthOut'])) {$currentRecord['BandwidthOut'] = $currentRecord['BandwidthOut']['avg'];}
                if (is_array($currentRecord['BandwidthTotal'])) {$currentRecord['BandwidthTotal'] = $currentRecord['BandwidthTotal']['avg'];}
                if (is_array($currentRecord['MemoryUsageTotal'])) {$currentRecord['MemoryUsageTotal'] = $currentRecord['MemoryUsageTotal']['avg'];}
                if (is_array($currentRecord['MemoryUsageUsed'])) {$currentRecord['MemoryUsageUsed'] = $currentRecord['MemoryUsageUsed']['avg'];}
                if (is_array($currentRecord['MemoryUsageFree'])) {$currentRecord['MemoryUsageFree'] = $currentRecord['MemoryUsageFree']['avg'];}
                if (is_array($currentRecord['MemoryUsagePercent'])) {$currentRecord['MemoryUsagePercent'] = $currentRecord['MemoryUsagePercent']['avg'];}
                if (is_array($currentRecord['DiskUsageUsed'])) {$currentRecord['DiskUsageUsed'] = $currentRecord['DiskUsageUsed']['avg'];}
                if (is_array($currentRecord['DiskUsageFree'])) {$currentRecord['DiskUsageFree'] = $currentRecord['DiskUsageFree']['avg'];}
                if (is_array($currentRecord['DiskUsagePercent'])) {$currentRecord['DiskUsagePercent'] = $currentRecord['DiskUsagePercent']['avg'];}
                if (is_array($currentRecord['DiskUsageTotal'])) {$currentRecord['DiskUsageTotal'] = $currentRecord['DiskUsageTotal']['avg'];}
                if (is_array($currentRecord['CPUUsagePercent'])) {$currentRecord['CPUUsagePercent'] = $currentRecord['CPUUsagePercent']['avg'];}
                array_push($fileData, $currentRecord);
                $recordCount++;
            }
        }
        return $fileData;
    }

    public function getSystemStats($receivedRange) {
        $this->logger->info('AppBundle\Services\StatsService\getSystemStats()');

        //1 - Get list of files based on the requested range
        $fileList = self::getFileList($receivedRange);
        $fileData = self::getFileData($fileList);

        $results['results'] = $fileData;
        $results['total'] = count($fileData);
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
