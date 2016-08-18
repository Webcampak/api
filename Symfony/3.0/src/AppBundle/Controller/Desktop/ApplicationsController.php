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

class ApplicationsController extends Controller {

    public function getApplicationsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ApplicationsController.php\getApplicationsAction() - Start');

        $sqlQuery = "
            SELECT
                APP.APP_ID APP_ID
                , APP.CODE CODE
                , APP.NAME NAME
            FROM
                APPLICATIONS APP
            ORDER BY APP.NAME
        ";
        $dbresults = $this->getDoctrine()
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery);

        $results = array();
        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);
        return new JsonResponse($results);
    }

    public function getCurrentApplicationsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ApplicationsController.php\getCurrentApplicationsAction() - Start');

        $tokenStorage = $this->container->get('security.token_storage');
        $userEntity = $tokenStorage->getToken()->getUser();
        
        $dbresults = array();
        if (is_a($userEntity, 'AppBundle\Entities\Database\Users')) {
            $sqlQuery = "
                SELECT
                    APP.APP_ID APP_ID
                    , APP.NAME NAME
                    , APP.CODE CODE
                FROM
                    GROUPS_APPLICATIONS GROAPP
                LEFT JOIN APPLICATIONS APP ON APP.APP_ID = GROAPP.APP_ID
                LEFT JOIN USERS USE ON USE.GRO_ID = GROAPP.GRO_ID
                WHERE
                    USE.USE_ID = :useId
                ORDER BY APP.NAME
            ";
            $dbresults = $this->getDoctrine()
                      ->getManager()
                      ->getConnection()
                      ->fetchAll($sqlQuery, array('useId' => $userEntity->getUseId()));
        }
        $results = array();
        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);
        return new JsonResponse($results);

    }

 }
