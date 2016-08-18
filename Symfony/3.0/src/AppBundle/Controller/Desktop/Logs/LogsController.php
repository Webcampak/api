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
namespace AppBundle\Controller\Desktop\Logs;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class LogsController extends Controller {

    public function getCaptureLogsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\LogsController.php\getCaptureLogs() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];

        $results = $this->get('app.svc.systemlogs')->getLogFile($receivedSourceid, 'capture');
        return new JsonResponse($results);
    }

    public function getConfigurationLogsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\LogsController.php\getConfigurationLogs() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];

        $results = $this->get('app.svc.systemlogs')->getLogFile($receivedSourceid, 'configuration');
        return new JsonResponse($results);
    }

    public function getCustomVideosLogsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\LogsController.php\getCustomVideosLogs() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];

        $results = $this->get('app.svc.systemlogs')->getLogFile($receivedSourceid, 'customvideos');
        return new JsonResponse($results);
    }

    public function getPostprodLogsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\LogsController.php\getPostprodLogs() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];

        $results = $this->get('app.svc.systemlogs')->getLogFile($receivedSourceid, 'posprod');
        return new JsonResponse($results);
    }

    public function getVideosLogsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\LogsController.php\getVideosLogs() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];

        $results = $this->get('app.svc.systemlogs')->getLogFile($receivedSourceid, 'videos');
        return new JsonResponse($results);
    }

 }
