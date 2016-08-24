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
namespace AppBundle\Controller\Desktop\Systemconfiguration;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class SystemConfigurationController extends Controller {

    public function getConfigurationAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SystemConfigurationController.php\getCaptureAction() - Start');

        $confFile = $this->container->getParameter('dir_config') . "config-general.cfg";
        $confSettingsFile = $this->container->getParameter('sys_config') . "config-general.json";

        $results = $this->get('app.svc.configuration')->getSystemConfiguration(
            $confFile
            , $confSettingsFile);

        return new JsonResponse($results);
    }

    public function updateConfigurationAction($inputParams, Request $request) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SystemConfigurationController.php\updateCaptureAction() - Start');

        $receivedName = $inputParams['NAME'];
        $receivedValue = $inputParams['VALUE'];

        $confFile = $this->container->getParameter('dir_config') . "config-general.cfg";
        $confSettingsFile = $this->container->getParameter('sys_config') . "config-general.json";

        $results = $this->get('app.svc.configuration')->updateSystemConfiguration(
            $request->getClientIp()
            , $receivedName
            , $receivedValue
            , $confFile
            , $confSettingsFile
            , $this->container
        );
        return new JsonResponse($results);
    }

 }
