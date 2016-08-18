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

class OnlineStatusController extends Controller {

    public function indexAction(Request $request) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\OnlineStatusController.php\indexAction() - Start');
        
        $tokenStorage = $this->container->get('security.token_storage');
        $authorizationChecker = $this->container->get('security.authorization_checker');
        
        $userEntity = $tokenStorage->getToken()->getUser();
        
        if ($userEntity && $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $results = array("success" => true
                 , "message" => "Server is online, user is authenticated on client and server"
                 , "status" => "AUTHENTICATED"
                 , "USERNAME" => $userEntity->getUsername());
        } else {
            $results = array("success" => true, "message" => "Server is online, user not authenticated on server", "status" => "NOTAUTHENTICATED");
        }
        return new JsonResponse($results);
    }
}



