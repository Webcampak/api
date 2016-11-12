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

class DesktopStatefulConfigurationController extends Controller {

    public function getStatefulConfigurationAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Authentication\DesktopStatefulConfigurationController.php\getStatefulConfiguration() - Start');

        $userEntity = $this->container->get('security.token_storage')->getToken()->getUser();
        $results = array('results' => array(), 'total' => 0);
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $receivedSenchaApp = 'WPAKD';
            if (isset($inputParams['SENCHA_APP'])) {$receivedSenchaApp = $inputParams['SENCHA_APP'];}
            $query = "
                SELECT USEPRE.USEPRE_ID, USEPRE.WIDGET, USEPRE.STATEFULCONFIG
                FROM  USERS_PREFERENCES USEPRE
                WHERE USEPRE.USE_ID = :useid AND SENCHA_APP = :senchaApp
            ";
            $dbresults = $this->getDoctrine()->getManager()->getConnection()->fetchAll($query, array(':useid' => $userEntity->getUseId(), 'senchaApp' => $receivedSenchaApp));
            $results = array('results' => $dbresults, 'total' => count($dbresults));
        }
        return new JsonResponse($results);
    }

    public function addUpdateStatefulConfigurationAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Authentication\DesktopStatefulConfigurationController.php\addStatefulConfiguration() - Start');

        //Feature disabled, necessary to look at previous version of the file to get some code sample

        $dbresults = array();
        $results = array();
        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);
        return new JsonResponse($results);
    }

    public function removeStatefulConfigurationAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Authentication\DesktopStatefulConfigurationController.php\removeStatefulConfiguration() - Start');

        $receivedUsepreId = $inputParams['USEPRE_ID'];

        if (intval($receivedUsepreId) > 0) {
            $usersPreferencesEntity = $this->getDoctrine()
                                        ->getRepository('AppBundle:UsersPreferences')
                                        ->find($receivedUsepreId);
            if ($usersPreferencesEntity) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($usersPreferencesEntity);
                $em->flush();
            }
        }
        $dbresults = array();

        $results = array();
        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);
        return new JsonResponse($results);
    }
 }
