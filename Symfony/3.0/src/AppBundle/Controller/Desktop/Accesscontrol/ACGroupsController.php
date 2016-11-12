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

use AppBundle\Entities\Database\Groups;
use AppBundle\Entities\Database\GroupsApplications;
use AppBundle\Entities\Database\GroupsPermissions;

class ACGroupsController extends Controller {

    public function getGroupsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupsAction() - Start');

        $query = "
            SELECT
                GRO_ID
                , NAME
                , NOTES
            FROM
                GROUPS
            ORDER BY NAME
        ";

        $dbresults = $this->getDoctrine()
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($query);

        $results = array();
        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);
        return new JsonResponse($results);
    }

    public function addGroupAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\addGroupAction() - Start');

        $receivedName = $inputParams['NAME'];
        $receivedNotes = $inputParams['NOTES'];

        $searchGroupname = $this->getDoctrine()
          ->getRepository('AppBundle:Groups')
          ->findOneByName($receivedName);
        if ($searchGroupname) {
            $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\addGroupAction() - Error: Group already exists in database');
            throw new \Exception("Error, Group already exists in database");
        } else {
            $newGroupEntity = new Groups();
            $newGroupEntity->setName($receivedName);
            $newGroupEntity->setNotes($receivedNotes);

            $em = $this->getDoctrine()->getManager();
            $em->persist($newGroupEntity);
            $em->flush();

            $results = array("success" => true, "message" => "Group added");
        }
        return new JsonResponse($results);
    }

    public function updateGroupAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\updateGroupAction() - Start');

        $groupEntity = $this->getDoctrine()
                            ->getRepository('AppBundle:Groups')
                            ->find($inputParams['GRO_ID']);

        $searchGroIdEntity = $this->getDoctrine()
                            ->getRepository('AppBundle:Groups')
                            ->findOneByName($inputParams['NAME']);

        if ($searchGroIdEntity & $groupEntity != $searchGroIdEntity) {
            throw new \Exception("Error, Group already exists in database");
        } else if ($groupEntity) {
            $groupEntity->setName($inputParams['NAME']);
            $groupEntity->setNotes($inputParams['NOTES']);

            $em = $this->getDoctrine()->getManager();
            $em->persist($groupEntity);
            $em->flush();
            return new JsonResponse(array("success" => true, "message" => "Group updated"));
        } else {
            throw new \Exception("Error, Group does not exist");
        }
    }

    public function removeGroupAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\removeGroupAction() - Start');

        $receivedGroId = $inputParams['GRO_ID'];
        if (isset($receivedGroId) && intval($receivedGroId) > 0) {
            $groupEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Groups')
                                ->find($receivedGroId);

            $em = $this->getDoctrine()->getManager();
            $em->remove($groupEntity);
            $em->flush();
            $results = array("success" => true, "message" => "Group deleted");
        } else {
            throw new \Exception("No group selected");
        }
        return new JsonResponse($results);
    }

    public function getGroupAvailableApplicationsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupAvailableApplicationsAction() - Start');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupAvailableApplicationsAction() - GRO_ID:' . $inputParams['GRO_ID']);
        $dbresults = array();
        if (isset($inputParams['GRO_ID']) && intval($inputParams['GRO_ID']) > 0) {
            $dbresults = $this->get('app.svc.groups')->getAvailableApplicationsByGroId($inputParams['GRO_ID']);
        }

        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function getGroupCurrentApplicationsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupCurrentApplicationsAction() - Start');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupCurrentApplicationsAction() - GRO_ID:' . $inputParams['GRO_ID']);
        $dbresults = array();
        if (isset($inputParams['GRO_ID']) && intval($inputParams['GRO_ID']) > 0) {
            $dbresults = $this->get('app.svc.groups')->getCurrentApplicationsByGroId($inputParams['GRO_ID']);
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function removeGroupCurrentApplicationsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\removeGroupCurrentApplicationsAction() - Start');

        $receivedGroAppId = $inputParams['GROAPP_ID'];
        if (isset($receivedGroAppId) && intval($receivedGroAppId) > 0) {
            $deleteGroupsApplicationsEntity = $this->getDoctrine()
                                                ->getRepository('AppBundle:GroupsApplications')
                                                ->find($receivedGroAppId);
            if ($deleteGroupsApplicationsEntity) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($deleteGroupsApplicationsEntity);
                $em->flush();
                $results = array("success" => true, "message" => "Application removed");

            }
        }
        return new JsonResponse($results);
    }


    public function removeGroupAvailableApplicationsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\removeGroupAvailableApplicationsAction() - Start');

        $receivedGroId = $inputParams['GRO_ID'];
        $receivedAppId = $inputParams['APP_ID'];

        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\removeGroupAvailableApplicationsAction() - GRO_ID:' . $receivedGroId);

        if (isset($receivedGroId) && intval($receivedGroId) > 0) {
            //Removal actually corresponds to adding a group to the other store
            $newGroupsApplicationsEntity = new GroupsApplications();

            $groupsEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Groups')
                                ->find($receivedGroId);

            $applicationsEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Applications')
                                ->find($receivedAppId);

            $newGroupsApplicationsEntity->setApp($applicationsEntity);
            $newGroupsApplicationsEntity->setGro($groupsEntity);

            $em = $this->getDoctrine()->getManager();
            $em->persist($newGroupsApplicationsEntity);
            $em->flush();

            $results = array("success" => true, "message" => "Modification done");
        } else {
            throw new \Exception("Error, Nothing selected");
        }
        return new JsonResponse($results);
    }

    public function getGroupAvailablePermissionsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupAvailablePermissionsAction() - GRO_ID:' . $inputParams['GRO_ID']);
        $dbresults = array();
        if (isset($inputParams['GRO_ID']) && intval($inputParams['GRO_ID']) > 0) {
            $dbresults = $this->get('app.svc.groups')->getAvailablePermissionsByGroId($inputParams['GRO_ID']);
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function removeGroupAvailablePermissionsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\removeGroupAvailablePermissionsAction() - Start');

        $receivedGroId = $inputParams['GRO_ID'];
        $receivedPerId = $inputParams['PER_ID'];

        if (isset($receivedGroId) && intval($receivedGroId) > 0) {
            //Removal actually corresponds to adding a group to the other store
            $newGroupsPermissionsEntity = new GroupsPermissions();

            $groupsEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Groups')
                                ->find($receivedGroId);

            $permissionsEntity = $this->getDoctrine()
                                ->getRepository('AppBundle:Permissions')
                                ->find($receivedPerId);

            $newGroupsPermissionsEntity->setPer($permissionsEntity);
            $newGroupsPermissionsEntity->setGro($groupsEntity);

            $em = $this->getDoctrine()->getManager();
            $em->persist($newGroupsPermissionsEntity);
            $em->flush();

            $results = array("success" => true, "message" => "Modification done");
        } else {
            throw new \Exception("Error, Nothing selected");
        }
        return new JsonResponse($results);
    }

    public function getGroupCurrentPermissionsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupCurrentPermissionsAction() - Start');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\getGroupCurrentPermissionsAction() - GRO_ID:' . $inputParams['GRO_ID']);

        $dbresults = array();
        if (isset($inputParams['GRO_ID']) && intval($inputParams['GRO_ID']) > 0) {
            $dbresults = $this->get('app.svc.groups')->getCurrentPermissionsByGroId($inputParams['GRO_ID']);
        }
        return new JsonResponse(array(
            'results' => $dbresults
            , 'total' =>  count($dbresults)
        ));
    }

    public function removeGroupCurrentPermissionsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACGroupsController.php\removeGroupCurrentPermissionsAction() - Start');

        $receivedGroPerId = $inputParams['GROPER_ID'];
        if (isset($receivedGroPerId) && intval($receivedGroPerId) > 0) {
            $deleteGroupsPermissionsEntity = $this->getDoctrine()
                                                ->getRepository('AppBundle:GroupsPermissions')
                                                ->find($receivedGroPerId);
            if ($deleteGroupsPermissionsEntity) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($deleteGroupsPermissionsEntity);
                $em->flush();
                $results = array("success" => true, "message" => "Permission removed");

            }
        }
        return new JsonResponse($results);
    }

 }

