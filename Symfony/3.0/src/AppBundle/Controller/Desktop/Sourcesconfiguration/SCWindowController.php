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

class SCWindowController extends Controller {

    private function getUserPermissions($userEntity) {
        $logger = $this->get('logger');
        $sqlQuery = "SELECT PER.NAME NAME
                     FROM GROUPS_PERMISSIONS GROPER
                     LEFT JOIN PERMISSIONS PER ON PER.PER_ID = GROPER.PER_ID
                     WHERE GROPER.GRO_ID = :receivedGroId
                    ORDER BY PER.NAME";
        $userPermissionsDbResults = $this->getDoctrine()
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedGroId' => $userEntity->getGro()->getGroId()));
        $userPermissions = array();
        foreach($userPermissionsDbResults as $key=>$value) {
            $logger->info('AppBundle\Controller\Desktop\SCWindowController.php\getUserPermissions() - User has permission: ' . $value['NAME']);
            array_push($userPermissions, $value['NAME']);
        }
        return $userPermissions;
    }

    public function getConfigurationTabsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCWindowController.php\getConfigurationTabsAction() - Start');

        $userEntity = $this->container
                        ->get('security.token_storage')
                        ->getToken()
                        ->getUser();
        $userPermissions = self::getUserPermissions($userEntity);
        $sysConfig = $this->container->getParameter('sys_config');

        $tabsAllowed = $this->get('app.svc.configuration')->getConfigurationTabs($sysConfig, $userPermissions);
        
        return new JsonResponse(array(
            'results' => $tabsAllowed
            , 'total' =>  count($tabsAllowed)
        ));
    }


 }
