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
namespace AppBundle\Controller\Misc;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\JsonResponse;

class SystemController extends Controller {

    public function systemRebootAction() {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\SystemController.php - Starting systemRebootAction()');

        $tokenStorage = $this->container->get('security.token_storage');
        $authorizationChecker = $this->container->get('security.authorization_checker');        
        $userEntity = $tokenStorage->getToken()->getUser();
        if ($userEntity && $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $userService = $this->get('app.svc.user');
            $userPermissions = $userService->getUserPermissions($userEntity);
            if (in_array('SOURCES_CONFIGURATION_EXPERT', $userPermissions)) {
                $createConfiguration = new Process('reboot');
                $createConfiguration->run();
                $results = array("success" => true, "message" => "Server will reboot soon");
            } else {
                $results = array("success" => false, "message" => "User not allowed to perform this operation");
            }
        } else {
            $results = array("success" => false, "message" => "User not authenticated");
        }
        return new JsonResponse($results);
    }

}
