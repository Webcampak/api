<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Finder\Finder;

class DownloadService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, DelegatingEngine $templating, FilesService $filesService) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
        $this->templating      = $templating;
        $this->filesService    = $filesService;
    }

    public function serveDirectory($completePath) {
        $this->logger->info('AppBundle\Services\DownloadService\serveDirectory() - Start');
        $finder = new Finder();
        $finder->in($completePath);
        $finder->sortByType();
        $finder->depth('== 0');
        $filesArray = array();
        foreach ($finder as $file) {
            $this->logger->info('AppBundle\Services\DownloadService\serveDirectory() - Found: ' . $file->getRelativePathname());
            $currentFile = array();
            if (is_dir($file->getRealpath())) {$currentFile['type'] = "[D]";}
            else {$currentFile['type'] = "[F]";}
            $currentFile['relativePath'] = $file->getRelativePathname();
            $currentFile['realPath'] = $file->getRealpath();
            $currentFile['fileSize'] = $this->filesService->getSymbolByQuantity(filesize($file->getRealpath()));
            array_push($filesArray, $currentFile);
        }
        return $this->templating->renderResponse('AppBundle:Download:index.html.php', array('files' => $filesArray));
    }

    public function serveFile($completePath, $pictureWidth = null) {
        $this->logger->info('AppBundle\Services\DownloadService\serveFile() - Path is a file');
        $fileFormat = pathinfo($completePath, PATHINFO_EXTENSION);
        $allowedExtensions = array('png', 'jpg', 'raw', 'txt', 'jpeg', 'json', 'mp3', 'JPG', 'JPEG', 'RAW', 'CR2', 'mp4', 'avi', 'jsonl', 'log');
        if (in_array($fileFormat, $allowedExtensions) && $pictureWidth === null) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_file($finfo, $completePath);
            finfo_close($finfo);
            $this->logger->info('AppBundle\Services\DownloadService\serveFile() - Extension: ' . $contentType);
            return new StreamedResponse(
                function () use ($completePath) {
                    readfile($completePath);
                }, 200, array('Content-Type' => $contentType)
            );
        } else if (intval($pictureWidth) > 0) {
            $this->logger->info('AppBundle\Services\DownloadService\serveFile() - Path is a picture to be resized');
            //Resize an image on the fly and serves it as a streamed response
            $originalPictureInfo = getimagesize($completePath);
            $originalPictureWidth = $originalPictureInfo[0];
            $originalPictureHeight = $originalPictureInfo[1];
            $pictureHeight = intval($pictureWidth * $originalPictureHeight / $originalPictureWidth);
            $this->logger->info('AppBundle\Services\DownloadService\serveFile() - Original Size: ' . $originalPictureWidth . 'x' . $originalPictureHeight);
            $this->logger->info('AppBundle\Services\DownloadService\serveFile() - Target Size: ' . $pictureWidth . 'x' . $pictureHeight);

            $dstPicture = \imagecreatetruecolor($pictureWidth, $pictureHeight);
            $srcPicture = \imagecreatefromjpeg($completePath);
            \imagecopyresized($dstPicture, $srcPicture, 0, 0, 0, 0, $pictureWidth, $pictureHeight, $originalPictureWidth, $originalPictureHeight);

            $tmpPath = tempnam("/tmp", "PIC");
            \imagejpeg($dstPicture, $tmpPath);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_file($finfo, $completePath);
            finfo_close($finfo);
            $this->logger->info('AppBundle\Services\DownloadService\serveFile() - Extension: ' . $contentType);

            return new StreamedResponse(
                function () use ($tmpPath) {
                    readfile($tmpPath);
                    unlink($tmpPath);
                }, 200, array('Content-Type' => $contentType)
            );
        } else {
            $response = new Response();
            $response->setContent('<html><body><h1>This type of file is not allowed</h1><p>File type:' . $fileFormat . '</p></body></html>');
            $response->setStatusCode(Response::HTTP_OK);
            $response->headers->set('Content-Type', 'text/html');
            return $response;
        }

    }

}
