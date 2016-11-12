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
namespace AppBundle\Controller\Desktop\Accesscontrol;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Entities\Database\Users;

class ACUsersController extends Controller {

    public function getUsersAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\Accesscontrol\ACUsersController.php\getUsersAction() - Start');

        $dbresults = $this->getDoctrine()
                  ->getManager()
                  ->getConnection()
                  ->fetchAll("
                      SELECT
                        USE_ID
                        , CUS_ID
                        , GRO_ID
                        , USERNAME
                        , CHANGE_PWD_FLAG
                        , ACTIVE_FLAG
                        , FIRSTNAME
                        , LASTNAME
                        , EMAIL
                        , LANG
                        , (SELECT COUNT(*) FROM LOGIN_HISTORY WHERE LOGIN_HISTORY.USERNAME = USERS.USERNAME AND LOGIN_HISTORY.USER_AGENT != 'CONNECTEDPING' ) AS LOG_COUNT
                      FROM USERS
                      ORDER BY USERNAME");

        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function addUserAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\Accesscontrol\ACUsersController.php\addUserAction() - Start');

        $searchUsername = $this->getDoctrine()
          ->getRepository('AppBundle:Users')
          ->findOneByUsername($inputParams['USERNAME']);
        if ($searchUsername) {
            throw new \Exception("Error, Username already exists in database");
        } else {
                $connectedUserEntity = $this->container
                                    ->get('security.token_storage')
                                    ->getToken()
                                    ->getUser();

                $passwordSalt = sha1($inputParams['USERNAME'] . microtime());

                $newPasswordEncoded = $this->get('security.encoder_factory')
                                        ->getEncoder($connectedUserEntity)
                                        ->encodePassword($inputParams['PASSWORD'], $passwordSalt);

                $inputParams['CUS'] = $this->getDoctrine()
                                    ->getRepository('AppBundle:Customers')
                                    ->find($inputParams['CUS_ID']);

                $inputParams['GRO'] = $this->getDoctrine()
                                    ->getRepository('AppBundle:Groups')
                                    ->find($inputParams['GRO_ID']);

                $newUsersEntity = new Users($inputParams['USERNAME'], $newPasswordEncoded, $passwordSalt, array());
                $newUsersEntity->updateUserEntity($inputParams);

                $em = $this->getDoctrine()->getManager();
                $em->persist($newUsersEntity);
                $em->flush();

                $results = array("success" => true, "message" => "User added");
        }
        return new JsonResponse($results);
    }

    public function updateUserAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\Accesscontrol\ACUsersController.php\updateUserAction() - Start');

        $updateUsersEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Users')
                                ->find($inputParams['USE_ID']);

        if ($updateUsersEntity) {
                $inputParams['CUS'] = $this->getDoctrine()
                                    ->getRepository('AppBundle:Customers')
                                    ->find($inputParams['CUS_ID']);

                $inputParams['GRO'] = $this->getDoctrine()
                                    ->getRepository('AppBundle:Groups')
                                    ->find($inputParams['GRO_ID']);

                if ($inputParams['PASSWORD'] != '') {
                    //Only update password if the we receive a non-empty one.
                    $connectedUserEntity = $this->container
                                        ->get('security.token_storage')
                                        ->getToken()
                                        ->getUser();
                    //Generate a new encoded password
                    $newPasswordEncoded = $this
                                            ->get('security.encoder_factory')
                                            ->getEncoder($connectedUserEntity)
                                            ->encodePassword($inputParams['PASSWORD'], $updateUsersEntity->getSalt());
                    $updateUsersEntity->setPassword($newPasswordEncoded);
                }
                $updateUsersEntity->updateUserEntity($inputParams);
                $em = $this->getDoctrine()->getManager();
                $em->persist($updateUsersEntity);
                $em->flush();
                $results = array("success" => true, "message" => "User updated");
        } else {
            throw new \Exception("Error, User does not exist in database, update not possible");
        }
        return new JsonResponse($results);
    }

    public function removeUserAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\Accesscontrol\ACUsersController.php\removeUserAction() - Start');
        if (isset($inputParams['USE_ID']) && intval($inputParams['USE_ID']) > 0) {
            $userEntity = $this->getDoctrine()
                            ->getRepository('AppBundle:Users')
                            ->find($inputParams['USE_ID']);

            if ($userEntity && $userEntity->getUsername() == "root") {
                throw new \Exception("Error, User root cannot be deleted");
            } else if ($userEntity) {
                $logger->info('AppBundle\Controller\Desktop\AdminUsersCustomersController.php\removeUserAction() - User will be deleted (USE_ID: ' . $userEntity->getUseId() . ' USERNAME:' . $userEntity->getUsername());
                $em = $this->getDoctrine()->getManager();
                $em->remove($userEntity);
                $em->flush();
                return new JsonResponse(array("success" => true, "message" => "User deleted"));
            } else {
                throw new \Exception("Error, Unable to find user");
            }
        } else {
            throw new \Exception("No user selected");
        }
    }


    public function getUserAvailableSourcesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\getUserAvailableSourcesAction() - USE_ID:' . $inputParams['USE_ID']);
        $dbresults = array();
        if (isset($inputParams['USE_ID']) && intval($inputParams['USE_ID']) > 0) {
            $dbresults = $this->get('app.svc.user')->getAvailableSourcesByUseId($inputParams['USE_ID']);
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function getUserCurrentSourcesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\getUserCurrentSourcesAction() - USE_ID:' . $inputParams['USE_ID']);
        $dbresults = array();
        if (isset($inputParams['USE_ID']) && intval($inputParams['USE_ID']) > 0) {
            $dbresults = $this->get('app.svc.user')->getCurrentSourcesByUseId($inputParams['USE_ID']);
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }


    public function removeUserAvailableSourcesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\removeUserAvailableSourcesAction() - Start');

        $receivedSouId = $inputParams['SOU_ID'];
        $receivedUseId = $inputParams['USE_ID'];

        $logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\removeUserAvailableSourcesAction() - SOU_ID:' . $receivedSouId);

        //Removal actually corresponds to adding a source to the other store
        $results = $this->get('app.svc.sources')->addUserToSource($receivedSouId, $receivedUseId);
        return new JsonResponse($results);
    }

    public function removeUserCurrentSourcesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\removeUserCurrentSourcesAction() - Start');

        $receivedUseSouId = $inputParams['USESOU_ID'];
        if (isset($receivedUseSouId) && intval($receivedUseSouId) > 0) {
            $userSourcesEntity = $this->getDoctrine()
                                    ->getRepository('AppBundle:UsersSources')
                                    ->find($receivedUseSouId);

            if ($userSourcesEntity->getUse()->getUsername() == 'root') {
                throw new \Exception("User root cannot be removed any sources");
            } else if ($userSourcesEntity) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($userSourcesEntity);
                $em->flush();
                $results = array("success" => true, "message" => "User removed");
            } else {
                throw new \Exception("User root cannot be removed from sources");
            }
        }
        return new JsonResponse($results);
    }

    public function updateUserCurrentSourcesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\updateUserCurrentSourcesAction() - Start');

        $receivedUsesouId = $inputParams['USESOU_ID'];
        $receivedAlertsFlag = $inputParams['ALERTS_FLAG'];
        $userSourcesEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:UsersSources')
                                ->find($receivedUsesouId);   
        if ($userSourcesEntity) {
            $userSourcesEntity->setAlertsFlag($receivedAlertsFlag);
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }
        $results = array("success" => true, "message" => "Alerts flag updated");
        return new JsonResponse($results);
    }    
    

 }
