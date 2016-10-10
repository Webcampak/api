<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;

class PicturesService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, PicturesDirectoryService $picturesDirectoryService, ConfigurationService $configurationService, $paramDirSources, $etcDir) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->picturesDirectoryService     = $picturesDirectoryService;
        $this->configurationService         = $configurationService;        
        $this->paramDirSources              = $paramDirSources;
        $this->etcDir                       = $etcDir;
        
    }

    public function getPicture($receivedSourceid = null, $receivedPictureDate = null) {
        $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Source: ' . $receivedSourceid .  ' - PictureDate: ' . $receivedPictureDate);
        if (intval($receivedSourceid) > 0) {
            $tmpresults = array();

            $latestDay = $this->picturesDirectoryService->getLatestPictureDay($receivedSourceid);
            $latestPicture = $this->picturesDirectoryService->latestPictureFile($receivedSourceid, $latestDay);
            $tmpresults['LAST'] = basename($latestPicture);

            if ($receivedPictureDate === null) {
                $receivedPictureDate = basename($latestPicture);
                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Latest Picture for source: ' . $receivedPictureDate);
            }
            
            if ($receivedPictureDate != "") {            
                $pictureDay = substr($receivedPictureDate, 0,8);
                $pictureDirectory = $this->paramDirSources . 'source' . $receivedSourceid . '/pictures/' . $pictureDay . '/';

                $listPictures = $this->picturesDirectoryService->listPicturesInDirectory($pictureDirectory, 'jpg', 'flat');

                $tmpresults['PICTURE'] = $receivedPictureDate;
                $tmpresults['PICTUREEXIF'] = '';
                if (is_file($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/' . $pictureDay . '/' . $receivedPictureDate)) {
                    $tmpresults['PICTUREJPGSIZE'] = filesize($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/' . $pictureDay . '/' . $receivedPictureDate);
                    try {
                        $tmpresults['PICTUREEXIF'] = json_encode(exif_read_data($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/' . $pictureDay . '/' . $receivedPictureDate, 'ANY_TAG'));
                    } catch (\RuntimeException $e) {
                        $tmpresults['PICTUREEXIF'] = null;
                    }
                }
                if (is_file($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/raw/' . $pictureDay . '/' . substr($receivedPictureDate, 0,14) . '.raw')) {
                    $tmpresults['PICTURERAWSIZE'] = filesize($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/raw/' . $pictureDay . '/' . substr($receivedPictureDate, 0,14) . '.raw');
                    try {
                        $tmpresults['PICTUREEXIF'] = json_encode(exif_read_data($this->paramDirSources . 'source' . $receivedSourceid . '/pictures/raw/' . $pictureDay . '/' . substr($receivedPictureDate, 0,14) . '.raw', 'ANY_TAG'));
                    } catch (\RuntimeException $e) {
                        $tmpresults['PICTUREEXIF'] = null;
                    }
                }
                
                //Get Picture Date
                $sourceTimezone = $this->configurationService->getSourceConfigurationParameterValue($this->etcDir . 'config-source' . $receivedSourceid . '.cfg', 'cfgcapturetimezone');
                $tmpresults['PICTUREDATE'] = \DateTime::createFromFormat('YmdHis', substr($tmpresults['PICTURE'], 0,14), new \DateTimeZone($sourceTimezone));            
                if ($tmpresults['PICTUREDATE'] instanceof \DateTime) {
                  $tmpresults['PICTUREDATE'] = $tmpresults['PICTUREDATE']->format('c');
                } else {
                   $tmpresults['PICTUREDATE'] = false; 
                }                
                
                if ($tmpresults['PICTURE'] == $tmpresults['LAST']) {
                    $tmpresults['LAST'] = null;
                }

                $originalPictureInfo = getimagesize($pictureDirectory . $receivedPictureDate);
                $tmpresults['PICTUREWIDTH'] = $originalPictureInfo[0];
                $tmpresults['PICTUREHEIGHT'] = $originalPictureInfo[1];
                $tmpresults['ZOOMLEVEL'] = 0;

                $nbPictures = count($listPictures);
                for($i=0;$i<$nbPictures; $i++) {
                    if (substr($listPictures[$i], 0,14) == substr($receivedPictureDate, 0,14)) {
                        if (isset($listPictures[$i-15])) {
                            if ($listPictures[$i-15] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Thumbnail 1 is: ' . $listPictures[$i-15]);
                                $tmpresults['THUMB1'] = $listPictures[$i-15];
                            }
                        } else {
                            $tmpresults['THUMB1'] = null;
                        }
                        if (isset($listPictures[$i-10])) {
                            if ($listPictures[$i-10] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Thumbnail 2 is: ' . $listPictures[$i-10]);
                                $tmpresults['THUMB2'] = $listPictures[$i-10];
                            }
                        } else {
                            $tmpresults['THUMB2'] = null;
                        }
                        if (isset($listPictures[$i-5])) {
                            if ($listPictures[$i-5] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Thumbnail 3 is: ' . $listPictures[$i-5]);
                                $tmpresults['THUMB3'] = $listPictures[$i-5];
                            }
                        } else {
                            $tmpresults['THUMB3'] = null;
                        }
                        if (isset($listPictures[$i-1])) {
                            if ($listPictures[$i-1] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Previous picture is: ' . $listPictures[$i-1]);
                                $tmpresults['PREVIOUS'] = $listPictures[$i-1];
                            }
                        } else {
                            $tmpresults['PREVIOUS'] = null;
                        }
                        if (isset($listPictures[$i+1])) {
                            if ($listPictures[$i+1] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Next picture is: ' . $listPictures[$i+1]);
                                $tmpresults['NEXT'] = $listPictures[$i+1];
                            }
                        } else {
                            $tmpresults['NEXT'] = null;
                        }
                        if (isset($listPictures[$i+5])) {
                            if ($listPictures[$i+5] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Thumbnail 4 is: ' . $listPictures[$i+5]);
                                $tmpresults['THUMB4'] = $listPictures[$i+5];
                            }
                        } else {
                            $tmpresults['THUMB4'] = null;
                        }
                        if (isset($listPictures[$i+10])) {
                            if ($listPictures[$i+10] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Thumbnail 5 is: ' . $listPictures[$i+10]);
                                $tmpresults['THUMB5'] = $listPictures[$i+10];
                            }
                        } else {
                            $tmpresults['THUMB5'] = null;
                        }

                        if (isset($listPictures[$i+15])) {
                            if ($listPictures[$i+15] != "") {
                                $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Thumbnail 6 is: ' . $listPictures[$i+15]);
                                $tmpresults['THUMB6'] = $listPictures[$i+15];
                            }
                        } else {
                            $tmpresults['THUMB6'] = null;
                        }
                    }
                }

                $dbresults = array();
                array_push($dbresults, $tmpresults);

                $results['results'] = $dbresults;
                $results['total'] = count($dbresults);
            } else {
                $results = array("success" => false, "title" => "No Pictures", "message" => "No pictures have been captured yet for the source");                
            }
        } else {
            $results = array("success" => false, "title" => "Source Error", "message" => "Unable to access the source");            
        }
        return $results;        
    }

    public function getSensors($receivedSourceid = null, $receivedPictureDate = null) {
        $this->logger->info('AppBundle\Services\PicturesService\getPicture() - Source: ' . $receivedSourceid .  ' - PictureDate: ' . $receivedPictureDate);
        if (intval($receivedSourceid) > 0) {

            if ($receivedPictureDate === null) {
                $receivedPictureDate = $this->picturesDirectoryService->getLatestPictureDay($receivedSourceid);
            }

            $tmpresults = array();

            $pictureDay = substr($receivedPictureDate, 0,8);
            $pictureDirectory = $this->paramDirSources . 'source' . $receivedSourceid . '/pictures/' . $pictureDay . '/';

            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('sensor-*.png');
            $finder->in($pictureDirectory);
            $sensorCount = 1;
            foreach ($finder as $file) {
                $this->logger->info('AppBundle\Services\PicturesService\getSensors() - Looking at file: ' . $file->getFilename());
                $tmpresults['SENSOR' . $sensorCount] = $pictureDay . "/" . $file->getFilename();
                $sensorCount++;
            }

            $dbresults = array();
            array_push($dbresults, $tmpresults);

            $results['results'] = $dbresults;
            $results['total'] = count($dbresults);
            return $results;
        } else {
            $results = array("success" => false, "title" => "Source Error", "message" => "Unable to access the source");            
        }
    }

}
