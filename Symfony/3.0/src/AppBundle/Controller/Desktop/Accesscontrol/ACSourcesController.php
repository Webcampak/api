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

use Symfony\Component\Console\Input\ArrayInput;

use AppBundle\Entities\Database\Sources;
use AppBundle\Entities\Database\UsersSources;

use AppBundle\Command\SourceCreateCommand;
use AppBundle\Classes\BufferedOutput;

class ACSourcesController extends Controller {

    public function getSourcesAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\getSourcesAction() - Start');

        $query = "
            SELECT
                SOU_ID
                , NAME
                , SOURCEID
                , WEIGHT
                , QUOTA
                , REMOTE_HOST
                , REMOTE_USERNAME
                , REMOTE_PASSWORD
            FROM
                SOURCES
            ORDER BY WEIGHT
        ";

        $dbresults = $this->getDoctrine()
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($query);

        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function addSourceAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\addSourceAction() - Start');

        $receivedName = $inputParams['NAME'];
        $receivedSourceId = $inputParams['SOURCEID'];
        $receivedWeight = $inputParams['WEIGHT'];
        $receivedQuota = $inputParams['QUOTA']; // We receive in GB, so multiply by 1000000000
        $receivedRemoteHost = $inputParams['REMOTE_HOST'];
        $receivedRemoteUsername = $inputParams['REMOTE_USERNAME'];
        $receivedRemotePassword = $inputParams['REMOTE_PASSWORD'];

        $searchSourceId = $this->getDoctrine()
                            ->getRepository('AppBundle:Sources')
                            ->findOneBySourceId($receivedSourceId);

        if (intval($receivedSourceId) <= 0) {
            $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\addSourceAction() - Error: Source ID must be positive');
            throw new \Exception("Error, Source ID must be greater than 0");
        } else if ($searchSourceId) {
            $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\addSourceAction() - Error: Source already exists in database');
            throw new \Exception("Error, Source already exists in database");
        } else {
            $sourceCreateCommand = new SourceCreateCommand();
            $sourceCreateCommand->setContainer($this->container);
            $input = new ArrayInput(array('--sourceid' => $receivedSourceId));
            $output = new BufferedOutput();
            $resultCode = $sourceCreateCommand->run($input, $output);
            $commandOutput = explode("\n", $output->getBuffer());
            foreach($commandOutput as $commandOutputLine) {
                $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\addSourceAction() - Console Subprocess: ' . $commandOutputLine);
            }
            $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\addSourceAction() - Command Result code: ' . $resultCode);


            $newSourceEntity = new Sources();
            $newSourceEntity->setName($receivedName);
            $newSourceEntity->setSourceId($receivedSourceId);
            $newSourceEntity->setWeight($receivedWeight);
            $newSourceEntity->setQuota($receivedQuota);
            $newSourceEntity->setRemoteHost($receivedRemoteHost);
            $newSourceEntity->setRemotePassword($receivedRemoteUsername);
            $newSourceEntity->setRemoteUsername($receivedRemotePassword);

            $em = $this->getDoctrine()->getManager();
            $em->persist($newSourceEntity);

            $searchRootUserEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Users')
                                ->findOneByUsername('root');

            $newUsersSourceEntity = new UsersSources();
            $newUsersSourceEntity->setSou($newSourceEntity);
            $newUsersSourceEntity->setUse($searchRootUserEntity);
            $newUsersSourceEntity->setAlertsFlag('N');

            $em = $this->getDoctrine()->getManager();
            $em->persist($newUsersSourceEntity);

            $em->flush();

            $query = "SELECT SOU_ID , NAME, SOURCEID, WEIGHT, QUOTA, REMOTE_HOST, REMOTE_USERNAME, REMOTE_PASSWORD
                FROM SOURCES
                WHERE SOU_ID = :souId
                ORDER BY WEIGHT";
            $dbresults = $this->getDoctrine()
                      ->getManager()
                      ->getConnection()
                      ->fetchAll($query, array('souId' => $newSourceEntity->getSouId()));

            $results = array("success" => true, "message" => "Source added", "results" => $dbresults, "total" => count($dbresults));
        }

        return new JsonResponse($results);
    }

    public function updateSourceAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\updateSourceAction() - Start');

        $updateSourcesEntity = $this->getDoctrine()
                ->getRepository('AppBundle:Sources')
                ->find($inputParams['SOU_ID']);

        $searchSourceId = $this->getDoctrine()
                            ->getRepository('AppBundle:Sources')
                            ->findOneBySourceId($inputParams['SOURCEID']);

        if ($searchSourceId && $updateSourcesEntity != $searchSourceId) {
            throw new \Exception("Error, Source already exists in database");
        } else if ($updateSourcesEntity) {
            if ($updateSourcesEntity->getSourceId() != $inputParams['SOURCEID']) {
                $this->get('app.svc.sources')->moveSource($updateSourcesEntity, $inputParams['SOU_ID'], $this->container);
            }
            $updateSourcesEntity->updateSourceEntity($inputParams);
            $em = $this->getDoctrine()->getManager();
            $em->persist($updateSourcesEntity);
            $em->flush();
            return new JsonResponse(array("success" => true, "message" => "Source updated"));
        } else {
            throw new \Exception("Error, Source does not exist");
        }
    }

    public function removeSourceAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\removeSourceAction() - Start');

        if (isset($inputParams['SOU_ID']) && intval($inputParams['SOU_ID']) > 0) {
            $sourceEntity = $this->getDoctrine()
                ->getRepository('AppBundle:Sources')
                ->find($inputParams['SOU_ID']);
            if ($sourceEntity) {
                $results = $this->get('app.svc.sources')->removeSource($sourceEntity, $this->container);
            } else {
                throw new \Exception("Error, Unable to find source");
            }
        } else {
            throw new \Exception("Error, No source selected");
        }
        return new JsonResponse($results);
    }


    public function getSourceAvailableUsersAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\getSourceAvailableUsersAction() - SOU_ID:' . $inputParams['SOU_ID']);
        $dbresults = array();
        if (isset($inputParams['SOU_ID']) && intval($inputParams['SOU_ID']) > 0) {
            $dbresults = $this->get('app.svc.sources')->getAvailableUsersBySouId($inputParams['SOU_ID']);
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function getSourceCurrentUsersAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\getSourceCurrentUsersAction() - SOU_ID:' . $inputParams['SOU_ID']);
        $dbresults = array();
        if (isset($inputParams['SOU_ID']) && intval($inputParams['SOU_ID']) > 0) {
            $dbresults = $this->get('app.svc.sources')->getCurrentUsersBySouId($inputParams['SOU_ID']);
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }


    public function removeSourceAvailableUsersAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\removeSourceAvailableUsersAction() - Start');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\removeSourceAvailableUsersAction() - SOU_ID:' . $inputParams['SOU_ID']);
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\removeSourceAvailableUsersAction() - USE_ID:' . $inputParams['USE_ID']);

        //Removal actually corresponds to adding a source to the other store
        $results = $this->get('app.svc.sources')->addUserToSource($inputParams['SOU_ID'], $inputParams['USE_ID']);
        return new JsonResponse($results);
    }

    public function removeSourceCurrentUsersAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACSourcesController.php\removeSourceCurrentUsersAction() - Start');

        $receivedUseSouId = $inputParams['USESOU_ID'];
        if (isset($receivedUseSouId) && intval($receivedUseSouId) > 0) {
            $userSourcesEntity = $this->getDoctrine()
                                    ->getRepository('AppBundle:UsersSources')
                                    ->find($receivedUseSouId);

            if ($userSourcesEntity->getUse()->getUsername() == 'root') {
                throw new \Exception("Error, User root cannot be removed from any sources");
            } else if ($userSourcesEntity) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($userSourcesEntity);
                $em->flush();
                $results = array("success" => true, "message" => "User removed");
            } else {
                throw new \Exception("Error, User root cannot be removed from any sources");
            }
        }
        return new JsonResponse($results);
    }
        
 }

