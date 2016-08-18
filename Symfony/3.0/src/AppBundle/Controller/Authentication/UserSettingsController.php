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
namespace AppBundle\Controller\Authentication;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserSettingsController extends Controller {

    public function getSettingsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Authentication\UserSettings.php\getSettingsAction() - Start');

        $tokenStorage = $this->container->get('security.token_storage');
        $authorizationChecker = $this->container->get('security.authorization_checker');

        $userEntity = $tokenStorage->getToken()->getUser();

        $dbresults = array();
        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $dbresults = $this->get('app.svc.user')->getSettings($userEntity);            
        }        
        
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

}
