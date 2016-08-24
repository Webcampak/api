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
namespace AppBundle\Controller\Desktop;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;

class AdministrativeController extends Controller {

    public function emptyAnswerAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AdministrativeController.php\emptyAnswerAction() - Start');

        $dbresults = array();

        $results = array();
        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);
        return new JsonResponse($results);
    }

    public function getTimezonesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AdministrativeController.php\getTimezonesAction() - Start');

        $timezones = array();
        $zones = timezone_identifiers_list();
        sort($zones);
        foreach ($zones as $zone) {
                $tmptimezones = array();
                $tmptimezones['NAME'] = $zone;
                array_push($timezones, $tmptimezones);
        }

        $results = array();
        $results['results'] = $timezones;
        $results['total'] = count($timezones);
        return new JsonResponse($results);
    }

    public function getUsbPortsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AdministrativeController.php\getUsbPortsAction() - Start');

        $detectedCameras = $this->get('app.svc.devices')->getUsbPorts();
        $results = array();
        $results['results'] = $detectedCameras;
        $results['total'] = count($detectedCameras);
        return new JsonResponse($results);
    }

    public function getCameraModelsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AdministrativeController.php\getCameraModelsAction() - Start');

        $compactibleCameras = array();

        $process = new Process('gphoto2 --list-cameras');
        $process->run();
        foreach (explode("\n", $process->getOutput()) as $gphotoOutput) {
            if (strpos($gphotoOutput,'"') !== false) {
                preg_match("/\".*?\"|\'.*?\'/", $gphotoOutput, $gphotoOutputCameraName);
                $gphotoOutput = trim($gphotoOutput);
                $gphotoOutput = str_replace('"', "", $gphotoOutput);
                $gphotoOutputCameraName = str_replace('"', "", $gphotoOutputCameraName);
                array_push($compactibleCameras, array(
                    'ID' => trim($gphotoOutputCameraName[0])
                    , 'NAME' => $gphotoOutput
                ));
            }
        }

        $results = array();
        $results['results'] = $compactibleCameras;
        $results['total'] = count($compactibleCameras);
        return new JsonResponse($results);
    }

    public function getPhidgetsPortsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AdministrativeController.php\getPhidgetsPortsAction() - Start');

        $configFile = $this->container->getParameter('dir_config') . "config-general.cfg";
        $phidgetsensors = $this->get('app.svc.phidgets')->getPhidgetsPorts($configFile);

        return new JsonResponse(array(
            'results' => $phidgetsensors
            , 'total' =>  count($phidgetsensors)
        ));
    }

 }




