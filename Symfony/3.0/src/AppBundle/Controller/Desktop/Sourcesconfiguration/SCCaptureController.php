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
use Symfony\Component\HttpFoundation\Request;

class SCCaptureController extends Controller {

    public function getCaptureAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCCaptureController.php\getCaptureAction() - Start');

        $receivedSourceid = $inputParams['SOURCEID'];

        $confFile = $this->container->getParameter('dir_etc') . "config-source" . $receivedSourceid . ".cfg";
        $confSettingsFile = $this->container->getParameter('sys_config') . "config-source.json";

        $results = $this->get('app.svc.configuration')->getSourceConfiguration(
            $receivedSourceid
            , $confFile
            , $confSettingsFile);

        return new JsonResponse($results);
    }

    public function updateCaptureAction($inputParams, Request $request) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCCaptureController.php\updateCaptureAction() - Start');


        $receivedSourceid = $inputParams['SOURCEID'];
        $receivedName = $inputParams['NAME'];
        $receivedValue = $inputParams['VALUE'];

        $confFile = $this->container->getParameter('dir_etc') . "config-source" . $receivedSourceid . ".cfg";
        $confSettingsFile = $this->container->getParameter('sys_config') . "config-source.json";

        $results = $this->get('app.svc.configuration')->updateSourceConfiguration(
            $request->getClientIp()
            , $receivedSourceid
            , $receivedName
            , $receivedValue
            , $confFile
            , $confSettingsFile
            , $this->container
        );
        return new JsonResponse($results);
    }

    public function getSectionCaptureAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCCaptureController.php\getSectionCaptureAction() - Start');

        $confSettingsFile = $this->container->getParameter('sys_config') . "config-source.json";

        $results = $this->get('app.svc.configuration')->getSectionConfiguration(
            $confSettingsFile
        );
        return new JsonResponse($results);
    }

 }
