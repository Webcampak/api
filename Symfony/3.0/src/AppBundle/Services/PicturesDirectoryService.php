<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;

class PicturesDirectoryService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, $paramDirSources) {
        $this->tokenStorage      = $tokenStorage;
        $this->em                   = $doctrine->getManager();
        $this->logger               = $logger;
        $this->connection           = $doctrine->getConnection();
        $this->doctrine             = $doctrine;
        $this->paramDirSources      = $paramDirSources;
    }

    public function getHoursFromCaptureDirectory($receivedSourceid = null, $receivedDay = null) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getHoursFromCaptureDirectory() - Source: ' . $receivedSourceid .  ' - Day: ' . $receivedDay);
        if (intval($receivedSourceid) > 0) {
            if ($receivedDay === null) {
                //If no day received, automatically looking for the latest directory with pictures
                $receivedDay = self::getLatestPictureDay($receivedSourceid);
            }

            $picturesDirectory = $this->paramDirSources . 'source' . $receivedSourceid . '/pictures/' . $receivedDay . '/';
            $this->logger->info('AppBundle\Services\PicturesDirectoryService\getHoursFromCaptureDirectory() - Looking inside directory: ' . $picturesDirectory);

            $checkpics = self::listPicturesInDirectory($picturesDirectory, 'jpg', 'time');

            $dbresults = array();
            for ($i=0;$i<24;$i++) {
                $tmpresults = array();
                if ($i < 10) {$currenthour = "0" . $i;} else {$currenthour = (string)$i;}
                $tmpresults['id'] = $currenthour;
                for ($j=0;$j<60;$j++) {
                    if ($j < 10) {$currentminute = "0" . $j;} else {$currentminute = (string)$j;}
                    if (isset($checkpics[$currenthour][$currentminute])) {
                        if ($checkpics[$currenthour][$currentminute] != "0") {
                            $tmpresults[$currentminute] = $checkpics[$currenthour][$currentminute];
                        }
                    } else {
                        $tmpresults[$currentminute] = "0";
                    }
                }
                array_push($dbresults, $tmpresults);
            }

            $results['results'] = $dbresults;
            $results['total'] = count($dbresults);
            return $results;
        } else {
            $results = array("success" => false, "title" => "Source Access", "message" => "Unable to access the source");                                        
        }
    }

    public function getFirstPictureDayAmongstAllSources() {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getFirstPictureDayAmongstAllSources()');
        
        $firstDay = null;        
        $availableSources = $this->doctrine
                                ->getRepository('AppBundle:Sources')->findAll();        
        foreach ($availableSources as $sourceEntity) {
            $this->logger->info('AppBundle\Services\PicturesDirectoryService\getFirstPictureDayAmongstAllSources() - Processing Source: ' . $sourceEntity->getSourceId());
            $sourceFirstDay = self::getFirstPictureDay($sourceEntity->getSourceId());
            if (($firstDay === null || $sourceFirstDay < $firstDay) && $sourceFirstDay !== null) {
                $firstDay = $sourceFirstDay;
            }           
        }    
        return  $firstDay;
        
    }
    
    public function getFirstPictureDay($sourceId) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getFirstPictureDay()');
        $firstDay = null;
        $finder = new Finder();
        $finder->directories();
        $finder->sortByName();
        $finder->directories()->name('20*');
        $finder->in($this->paramDirSources . 'source' . $sourceId . '/pictures/');
        foreach ($finder as $directory) {
            $finderFiles = new Finder();
            $finderFiles->files();
            $finderFiles->files()->name('20*.jpg');
            $finderFiles->in($this->paramDirSources . 'source' . $sourceId . '/pictures/' . $directory->getFilename() . '/');
            if (iterator_count($finderFiles) > 0) {
                $firstDay = $directory->getFilename();
                $this->logger->info('AppBundle\Services\PicturesDirectoryService\getFirstPictureDay() - First day with pictures is: ' . $firstDay . ' with ' . iterator_count($finderFiles) . ' JPG pictures');
                break;
            }
        }
        return $firstDay;
    }

    public function getLatestPictureDay($sourceId) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getLatestPictureDay()');
        $baseDir = $this->paramDirSources . 'source' . $sourceId . '/pictures/';
        if (is_dir($baseDir)) {
            $latestDay = null;
            $finder = new Finder();
            $finder->directories();
            $finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) { return strcmp($b->getRealpath(), $a->getRealpath()); });
            $finder->directories()->name('20*');
            $finder->in($this->paramDirSources . 'source' . $sourceId . '/pictures/');
            foreach ($finder as $directory) {
                $finderFiles = new Finder();
                $finderFiles->files();
                $finderFiles->files()->name('20*.jpg');
                $finderFiles->in($this->paramDirSources . 'source' . $sourceId . '/pictures/' . $directory->getFilename() . '/');
                if (iterator_count($finderFiles) > 0) {
                    $latestDay = $directory->getFilename();
                    $this->logger->info('AppBundle\Services\PicturesDirectoryService\getLatestPictureDay() - Latest day with pictures is: ' . $latestDay . ' with ' . iterator_count($finderFiles) . ' JPG pictures');
                    break;
                }
            }
            return $latestDay;            
        }
        return null;
    }

    public function getLatestPictureForSource($sourceId) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getLatestPictureForSource()');
        
        $latestDay = self::getLatestPictureDay($sourceId);
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getLatestPictureForSource() - ' . $sourceId . ' - Latest Day: ' . $latestDay);
                
        $latestPicture = self::latestPictureFile($sourceId, $latestDay);
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getLatestPictureForSource() - ' . $sourceId . ' - Latest Picture: ' . $latestPicture);
                        
        return $latestPicture;         
    }    
    
    public function latestPictureFile($sourceId, $pictureDay) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\latestPictureFile()');
        $baseDir = $this->paramDirSources . 'source' . $sourceId . '/pictures/' . $pictureDay . '/';
        if (is_dir($baseDir)) {
            $latestPicture = null;
            $finder = new Finder();
            $finder->files();
            $finder->sort(function (\SplFileInfo $a, \SplFileInfo $b) { return strcmp($b->getRealpath(), $a->getRealpath()); });
            $finder->files()->name('20*.jpg');
            $finder->in($this->paramDirSources . 'source' . $sourceId . '/pictures/' . $pictureDay . '/');
            foreach ($finder as $file) {
                if (getimagesize($file->getRealpath()) !== false) {
                    $latestPicture = $file->getFilename();
                    $this->logger->info('AppBundle\Services\PicturesDirectoryService\getLatestPictureDay() - Latest picture is: ' . $latestPicture . ' on Day: ' . $pictureDay);
                    break;
                }
            }
            return $latestPicture;            
        }
        return null;
    }

    public function listPicturesInDirectory($picturesDirectory, $fileFormat = 'jpg', $outputType = "flat") {
        //outputType takes two values:
        // - flat to return an array of Filenames
        // - time to return a multidimensional array using hours and minutes
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\listPicturesInDirectory()');
        $finder = new Finder();
        $finder->files();
        $finder->sortByName();
        $finder->files()->name('20*.' . $fileFormat);
        $finder->in($picturesDirectory);
        $checkpics = array();
        foreach ($finder as $file) {
            $this->logger->info('AppBundle\Services\PicturesDirectoryService\listPicturesInDirectory() - Looking at file: ' . $file->getFilename());
            if ($outputType == "time") {
                $currenthour = substr($file->getFilename(), 8,2);
                $currentminute = substr($file->getFilename(),10,2);
                //SEND BACK FILENAME
                $checkpics[$currenthour][$currentminute] = $file->getFilename();
            } else if ($outputType == "size") {     
                $fileTime = substr($file->getFilename(), 0,12);
                $checkpics[$fileTime] = array(
                    'filename' => $file->getFilename()
                    , 'size' => $file->getSize()
                    );
            } else {
                array_push($checkpics, $file->getFilename());
            }
        }
        return $checkpics;
    }

    public function listDaysBetweenTwoDates($startDate, $endDate) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\listDaysBetweenTwoDates()');
        // takes two dates formatted as YYYYMMDD and creates an inclusive array of the dates between the from and to dates.
        // Taken from: http://stackoverflow.com/questions/4312439/php-return-all-dates-between-two-dates-in-an-array
        $dateRange=array();
        if (intval($startDate) > 0 && intval($endDate) > 0) {
            $iDateFrom = mktime(1, 0, 0, substr($startDate, 4, 2), substr($startDate, 6, 2), substr($startDate, 0, 4));
            $iDateTo = mktime(1, 0, 0, substr($endDate, 4, 2), substr($endDate, 6, 2), substr($endDate, 0, 4));
            if ($iDateTo >= $iDateFrom) {
                array_push($dateRange, date('Ymd', $iDateFrom)); // first entry
                while ($iDateFrom < $iDateTo) {
                    $iDateFrom += 86400; // add 24 hours
                    array_push($dateRange, date('Ymd', $iDateFrom));
                }
            }
        }
        return $dateRange;
    }

    public function buildDatePickerDisabledDays($allDays, $firstDay, $lastDay) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\listDaysBetweenTwoDates()');

        $dateRange = self::listDaysBetweenTwoDates($firstDay, $lastDay);
        $disabledDays = null;
        foreach($dateRange as $idx=>$currentday) {
            if (!isset($allDays[$currentday])) {
                if ($disabledDays !== null) { $disabledDays = $disabledDays . ", ";}
                $disabledDays = $disabledDays . "'" . substr($currentday, 4,2) . "/" . substr($currentday, 6,2) . "/" .  substr($currentday, 0,4) . "'";
            }
        }
        return $disabledDays;
    }

    public function convertDay($day) {
        if ($day !== null) {
            $day = mktime(8, 8, 8, substr($day, 4,2), substr($day, 6,2), substr($day, 0,4))  * 1000;
        }
        return $day;
    }

    public function getDaysFromCaptureDirectory($receivedSourceid = null) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\getDaysFromCaptureDirectory() - Source: ' . $receivedSourceid);
        if (intval($receivedSourceid) > 0) {

            $finder = new Finder();
            $finder->directories();
            $finder->sortByName();
            $finder->directories()->name('20*');
            $finder->in($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/');
            $firstDay = null;
            $lastDay = null;
            $allDays = array();
            foreach ($finder as $directory) {
                $finderFiles = new Finder();
                $finderFiles->files();
                $finderFiles->files()->name('20*.jpg');
                $finderFiles->in($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/' . $directory->getFilename() . '/');
                if (iterator_count($finderFiles) > 0) {
                    if ($firstDay === null) {$firstDay = $directory->getFilename();}
                    $lastDay = $directory->getFilename();
                    $allDays[$lastDay] = iterator_count($finderFiles);
                    $this->logger->info('AppBundle\Services\PicturesDirectoryService\getHoursFromCaptureDirectory() - ' . iterator_count($finderFiles) . ' JPG Pictures available on: ' . $directory->getFilename());
                }
            }

            $results['results'] = array(
                'MIN' => self::convertDay($firstDay)
                , 'MAX' => self::convertDay($lastDay)
                , 'DISABLED' => self::buildDatePickerDisabledDays($allDays, $firstDay, $lastDay
            ));
            $results['total'] = 1;
            return $results;
        } else {
            $results = array("success" => false, "title" => "Source Access", "message" => "Unable to access the source");                                        
        }
    }

    public function countPicturesForDay($sourceId, $day) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\countPicturesForDay()');
        if (is_dir($this->paramDirSources . 'source' . $sourceId . '/pictures/' . $day . '/')) {
            $finderFiles = new Finder();
            $finderFiles->files();
            $finderFiles->files()->name('20*.jpg');
            $finderFiles->in($this->paramDirSources . 'source' . $sourceId . '/pictures/' . $day . '/');
            return iterator_count($finderFiles);
        } else {
            return 0;
        }
    }

    /**
     * Calculate the total size of pictures (raw and jpg) for a particular day
     *
     * @param int   $sourceId    The SOURCEID of the source
     * @param int   $day         Day in numeric format (20160324 for example)
     *
     * @return int Total size in bytes for the specified day
     */       
    public function sizePicturesForDay($sourceId, $day) {
        $this->logger->info('AppBundle\Services\PicturesDirectoryService\sizePicturesForDay()');
        $totalSize = 0;
        if (is_dir($this->paramDirSources . 'source' . $sourceId . '/pictures/' . $day . '/')) {
            $finderFiles = new Finder();
            $finderFiles->files();
            $finderFiles->files()->name('20*.jpg');
            $finderFiles->in($this->paramDirSources . 'source' . $sourceId . '/pictures/' . $day . '/');
            foreach($finderFiles as $file) {
                $totalSize += $file->getSize();
            }
        }
        if (is_dir($this->paramDirSources . 'source' . $sourceId . '/pictures/raw/' . $day . '/')) {
            $finderFiles = new Finder();
            $finderFiles->files();
            $finderFiles->files()->name('20*.raw');
            $finderFiles->in($this->paramDirSources . 'source' . $sourceId . '/pictures/raw/' . $day . '/');
            foreach($finderFiles as $file) {
                $totalSize += $file->getSize();
            }
        }
        return $totalSize;
    }

}
