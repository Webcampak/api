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
namespace AppBundle\Controller\Desktop\Sourcesconfiguration;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class SCMiscController extends Controller {

    public function getWatermarkFilesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCMiscController.php\getWatermarkFilesAction() - Start');

        $watermarkfiles = $this->get('app.svc.sources')->getWatermarkFiles($inputParams['SOURCEID']);

        return new JsonResponse(array(
            'results' => $watermarkfiles
            , 'total' =>  count($watermarkfiles)
        ));
    }

    public function getFontsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCMiscController.php\getFontsAction() - Start');

        $fonts = array();
        exec('mogrify -list font | grep Font', $fontlist, $ret);
        $cptfonts = sizeof($fontlist);
        for ($i=0;$i<$cptfonts;$i++) {
                $tmpfonts = array();
                $tmpfonts['NAME'] = trim(str_replace("Font:", "", $fontlist[$i]));
                array_push($fonts, $tmpfonts);
        }

        return new JsonResponse(array(
            'results' => $fonts
            , 'total' =>  count($fonts)
        ));
    }

    public function getPhidgetSensorsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCMiscController.php\getPhidgetSensorsAction() - Start');

        $configFile = $this->container->getParameter('dir_etc') . "config-general.cfg";
        $phidgetsensors = $this->get('app.svc.phidgets')->getPhidgetsPorts($configFile);

        return new JsonResponse(array(
            'results' => $phidgetsensors
            , 'total' =>  count($phidgetsensors)
        ));
    }
    
    public function getCaptureScheduleAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCMiscController.php\getCaptureScheduleAction() - Start');

        $userEntity = $this->container->get('security.token_storage')->getToken()->getUser();        
                
        $availableSources = $this->get('app.svc.user')->getCurrentSourcesByUseId($userEntity->getUseId());
        $dbresults = array();
        foreach ($availableSources as $source) {
            $scheduleFile = $this->container->getParameter('dir_etc') . "config-source" . $source['SOURCEID'] . "-schedule.json";
            $jsonContent = "";
            if (is_file($scheduleFile)) {$jsonContent = file_get_contents($scheduleFile);}            
            array_push($dbresults, array('JSON' => $jsonContent, 'SOURCEID' => $source['SOURCEID']));
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  1
        ));
    }

    public function saveCaptureScheduleAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCMiscController.php\getCaptureScheduleAction() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];        
        $receivedJson = $inputParams['JSON'];                
        if ($this->get('app.svc.user')->hasCurrentUserAccessToSourceId($receivedSourceid)) {
            $scheduleFile = $this->container->getParameter('dir_etc') . "config-source" . $receivedSourceid . "-schedule.json";
            file_put_contents($scheduleFile, $receivedJson);            
        }
        return new JsonResponse( array("success" => true, "message" => "User updated"));
    }      

 }
