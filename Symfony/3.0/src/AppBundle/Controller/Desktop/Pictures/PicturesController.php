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
namespace AppBundle\Controller\Desktop\Pictures;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class PicturesController extends Controller {

    public function getHoursListAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\PicturesController.php\getHoursListAction() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];
        $receivedSelectedDay = $inputParams['SELECTEDDAY'];

        $results = $this->get('app.svc.pictures.directory')->getHoursFromCaptureDirectory($receivedSourceid, $receivedSelectedDay);
        return new JsonResponse($results);

    }

    public function getDaysListAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\PicturesController.php\getDaysListAction() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];

        $results = $this->get('app.svc.pictures.directory')->getDaysFromCaptureDirectory($receivedSourceid);
        return new JsonResponse($results);
    }

    public function getPictureAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\PicturesController.php\getPictureAction() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];
        $receivedPictureDate = $inputParams['PICTUREDATE'];

        $results = $this->get('app.svc.pictures')->getPicture($receivedSourceid, $receivedPictureDate);
        return new JsonResponse($results);
    }

    public function getSensorsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\PicturesController.php\getSensorsAction() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];
        $receivedPictureDate = $inputParams['PICTUREDATE'];

        $results = $this->get('app.svc.pictures')->getSensors($receivedSourceid, $receivedPictureDate);
        return new JsonResponse($results);
    }

 }
