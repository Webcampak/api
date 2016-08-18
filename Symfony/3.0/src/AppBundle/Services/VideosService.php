<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Finder\Finder;

class VideosService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, PicturesDirectoryService $picturesDirectoryService, $paramDirSources) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->picturesDirectoryService     = $picturesDirectoryService;
        $this->paramDirSources              = $paramDirSources;
    }

    public function getVideos($receivedSourceid = null) {
        $this->logger->info('AppBundle\Services\VideosService\getVideo() - Source: ' . $receivedSourceid);
        if (intval($receivedSourceid) > 0) {

            $dbresults = array();

            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('20*.avi');
            $finder->in($this->paramDirSources . 'source' . $receivedSourceid . '/videos/');
            foreach ($finder as $file) {
                $this->logger->info('AppBundle\Services\VideosService\getVideo() - Looking at file: ' . $file->getFilename());
                $videoDate = substr($file->getFilename(), 0,8);
                if (substr($file->getFilename(), 8) == ".1080p.avi" || substr($file->getFilename(), 8) == ".720p.avi" || substr($file->getFilename(), 8) == ".480p.avi" || substr($file->getFilename(), 8) == ".custom.avi") {
                    $videoName = $file->getFilename(); // Video is an automated generated video
                } else {
                    $videoName = substr($file->getFilename(), 9); // Video is a custom generated video (location 9 to remove _)
                }
                if (strpos($file->getFilename(),"1080p.avi") !== false) {$videoFormat = "1080p";}
                elseif (strpos($file->getFilename(),"720p.avi") !== false) {$videoFormat = "720p";}
                elseif (strpos($file->getFilename(),"480p.avi") !== false) {$videoFormat = "480p";}
                elseif (strpos($file->getFilename(),"custom.avi") !== false) {$videoFormat = "custom";}

                $videoSize = $file->getSize();
                $videoAvi = $file->getFilename();

                if (is_file($this->paramDirSources . 'source' . $receivedSourceid . '/videos/' . $file->getFilename() . '.jpg')) {
                    $videoJpg = $file->getFilename() . '.jpg';
                    $jpgPictureInfo = getimagesize($this->paramDirSources . 'source' . $receivedSourceid . '/videos/' . $file->getFilename() . '.jpg');
                    $videoJpgWidth = $jpgPictureInfo[0];
                    $videoJpgHeight = $jpgPictureInfo[1];
                } else {
                    $videoJpg = '';
                    $videoJpgWidth = '';
                    $videoJpgHeight = '';
                }
                if (is_file($this->paramDirSources . 'source' . $receivedSourceid . '/videos/' . $file->getFilename() . '.mp4')) {
                    $videoMp4 = $file->getFilename() . '.mp4';
                } else {$videoMp4 = '';}

                array_push($dbresults, array(
                    'NAME' => $videoName
                    , 'FILENAME' => $file->getFilename()
                    , 'DATE' => $videoDate
                    , 'FORMAT' => $videoFormat
                    , 'SIZE' => $videoSize
                    , 'AVI' => $videoAvi
                    , 'MP4' => $videoMp4
                    , 'JPG' => $videoJpg
                    , 'JPGWIDTH' => $videoJpgWidth
                    , 'JPGHEIGHT' => $videoJpgHeight
                ));
            }
            $results['results'] = $dbresults;
            $results['total'] = count($dbresults);
            return $results;
        } else {
            $results = array("success" => false, "title" => "Source Access", "message" => "Unable to access the source");                                        
        }
    }

    public function getDaysFromVideoDirectory($receivedSourceid = null) {
        $this->logger->info('AppBundle\Services\VideosService\getDaysFromVideoDirectory() - Source: ' . $receivedSourceid);
        if (intval($receivedSourceid) > 0) {

            $finder = new Finder();
            $finder->files();
            $finder->sortByName();
            $finder->files()->name('20*.avi');
            $finder->in($this->paramDirSources . 'source' . $receivedSourceid . '/videos/');
            $firstDay = null;
            $lastDay = null;
            $allDays = array();
            foreach ($finder as $file) {
                $currentDay = substr($file->getFilename(), 0,8);
                if ($firstDay === null) {$firstDay = $currentDay;}
                $lastDay = $currentDay;
                if (array_key_exists ($lastDay, $allDays)) {
                    $allDays[$lastDay] = $allDays[$lastDay] + 1;
                } else {
                    $allDays[$lastDay] = 1;
                }
            }

            $results['results'] = array(
                'MIN' => $this->picturesDirectoryService->convertDay($firstDay)
                , 'MAX' => $this->picturesDirectoryService->convertDay($lastDay)
                , 'DISABLED' => $this->picturesDirectoryService->buildDatePickerDisabledDays($allDays, $firstDay, $lastDay
            ));

            $results['total'] = 1;
            return $results;
        } else {
            $results = array("success" => false, "title" => "Source Access", "message" => "Unable to access the source");                                        
        }
    }

}
