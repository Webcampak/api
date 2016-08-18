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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusController extends Controller {

    public function indexAction(Request $request) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\StatusController.php\indexAction() - Start');
        
        $receivedUsername = $request->request->get('USERNAME');

        $tokenStorage = $this->container->get('security.token_storage');
        $authorizationChecker = $this->container->get('security.authorization_checker');
        
        $userEntity = $tokenStorage->getToken()->getUser();
        
        if ($userEntity && $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $results = array(
                "success" => true
                , "build" => $this->get('app.svc.status')->getBuildVersion()
                , "uptime" => $this->get('app.svc.status')->getSystemUptime()                
                , "disk" => $this->get('app.svc.status')->getDiskStatus()
                , "cameras" => $this->get('app.svc.status')->getCameras()
                , "bootdate" => $this->get('app.svc.status')->getSystemBootDate()                                
                , "sources" => $this->get('app.svc.status')->getSourcesStatus()
                , "authentication" => $this->get('app.svc.status')->getAuthenticationStatus($this, $receivedUsername)
            );            
        } else {
            $results = array(
                "success" => true
                , "build" => $this->get('app.svc.status')->getBuildVersion()
                , "authentication" => $this->get('app.svc.status')->getAuthenticationStatus($this, $receivedUsername)
            );             
        }

        return new JsonResponse($results);
    }


}



