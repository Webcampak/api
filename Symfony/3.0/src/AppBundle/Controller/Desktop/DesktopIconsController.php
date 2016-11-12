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

use AppBundle\Entities\Database\UsersIcons;

class DesktopIconsController extends Controller {

    public function getDesktopAvailableIconsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopIconsController.php\getDesktopAvailableIconsAction() - Start');

        $tokenStorage = $this->container->get('security.token_storage');
        $authorizationChecker = $this->container->get('security.authorization_checker');        
        $userEntity = $tokenStorage->getToken()->getUser();

        $dbresults = array();
        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $sqlQuery = "
                SELECT
                    APP.APP_ID APP_ID
                    , APP.NAME NAME
                    , APP.CODE CODE
                    , APP.NOTES NOTES
                FROM
                    GROUPS_APPLICATIONS GROAPP
                LEFT JOIN APPLICATIONS APP ON APP.APP_ID = GROAPP.APP_ID
                LEFT JOIN USERS USE ON USE.GRO_ID = GROAPP.GRO_ID
                WHERE
                    USE.USE_ID = :useId
                    AND APP.APP_ID NOT IN (SELECT APP_ID FROM USERS_ICONS WHERE USE_ID = :useId AND ICON_VISIBLE_FLAG = 'Y')
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

    public function getDesktopCurrentIconsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopIconsController.php\getDesktopCurrentIconsAction() - Start');

        $tokenStorage = $this->container->get('security.token_storage');
        $authorizationChecker = $this->container->get('security.authorization_checker');
        
        $userEntity = $tokenStorage->getToken()->getUser();

        $dbresults = array();
        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $sqlQuery = "
                SELECT
                    APP.APP_ID APP_ID
                    , APP.NAME NAME
                    , APP.CODE CODE
                    , APP.NOTES NOTES
                    , USEICO.USEICO_ID
                    , USEICO.ICON_VISIBLE_FLAG
                    , USEICO.ICON_X_COORDINATE
                    , USEICO.ICON_Y_COORDINATE
                FROM
                    USERS_ICONS USEICO
                LEFT JOIN APPLICATIONS APP ON USEICO.APP_ID = APP.APP_ID
                WHERE
                    USEICO.ICON_VISIBLE_FLAG = 'Y'
                    AND USEICO.USE_ID = :useId
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

    public function removeDesktopAvailableIconsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopIconsController.php\removeDesktopAvailableIconsAction() - Start');

        $receivedAppId = $inputParams['APP_ID'];
        if (isset($receivedAppId) && intval($receivedAppId) > 0) {
            //Removal actually corresponds to adding an application to the other store
            $userEntity = $this->container->get('security.token_storage')->getToken()->getUser();

            $newUsersIconsEntity = new UsersIcons();
            $applicationsEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Applications')
                                ->find($receivedAppId);

            $newUsersIconsEntity->setApp($applicationsEntity);
            $newUsersIconsEntity->setUse($userEntity);
            $newUsersIconsEntity->setIconVisibleFlag('Y');

            $em = $this->getDoctrine()->getManager();
            $em->persist($newUsersIconsEntity);
            $em->flush();

            $results = array("success" => true, "message" => "Modification done");
        } else {
            throw new \Exception("Error, Nothing selected");
        }
        return new JsonResponse($results);
    }


    public function removeDesktopCurrentIconsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopIconsController.php\removeDesktopCurrentIconsAction() - Start');

        $receivedUseAppId = $inputParams['USEICO_ID'];
        $results = array("false" => true, "message" => "Unable to remove application");
        if (isset($receivedUseAppId) && intval($receivedUseAppId) > 0) {
            $deleteUsersIconsEntity = $this->getDoctrine()
                                                ->getRepository('AppBundle:UsersIcons')
                                                ->find($receivedUseAppId);
            if ($deleteUsersIconsEntity) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($deleteUsersIconsEntity);
                $em->flush();
                $results = array("success" => true, "message" => "Modification done");

            }
        }
        return new JsonResponse($results);
    }

    public function updateDesktopCurrentIconsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopIconsController.php\updateDesktopCurrentIconsAction() - Start');

        $userEntity = $this->container->get('security.token_storage')->getToken()->getUser();

        $usersApplicationEntity = $this->getDoctrine()
                                    ->getRepository('AppBundle:UsersIcons')
                                    ->findOneBy(array(
                                        'use' => $userEntity
                                        , 'useicoId' => $inputParams['USEICO_ID']
                                    ));
        if($usersApplicationEntity) {
            $usersApplicationEntity->setIconXCoordinate($inputParams['ICON_X_COORDINATE']);
            $usersApplicationEntity->setIconYCoordinate($inputParams['ICON_Y_COORDINATE']);

            $em = $this->getDoctrine()->getManager();
            $em->persist($usersApplicationEntity);
            $em->flush();
            return new JsonResponse(array("success" => true, "message" => "Location saved"));
        } else {
            throw new \Exception("Error, Unable to save icon location");
        }
    }


 }


