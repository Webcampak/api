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

use AppBundle\Entities\Database\Customers;

class ACCustomersController extends Controller {

    public function getCustomersAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACCustomersController.php\getCustomersAction() - Start');

        $query = "
            SELECT
                CUS_ID
                , NAME
            FROM
                CUSTOMERS
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

    public function addCustomerAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACCustomersController.php\addCustomerAction() - Start');

        $receivedName = $inputParams['NAME'];

        $searchCustomername = $this->getDoctrine()
          ->getRepository('AppBundle:Customers')
          ->findOneByName($receivedName);
        if ($searchCustomername) {
            $logger->info('AppBundle\Controller\Desktop\ACCustomersController.php\addCustomerAction() - Error: Customer already exists in database');
            throw new \Exception("Error, Customer already exists in database");
        } else {
            $newCustomerEntity = new Customers();
            $newCustomerEntity->setName($receivedName);

            $em = $this->getDoctrine()->getManager();
            $em->persist($newCustomerEntity);
            $em->flush();

            $results = array("success" => true, "message" => "Customer added");
        }

        return new JsonResponse($results);
    }

    public function updateCustomerAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACCustomersController.php\updateCustomerAction() - Start');

        $receivedCusId = $inputParams['CUS_ID'];
        $receivedName = $inputParams['NAME'];

        $updateCustomersEntity = $this->getDoctrine()
                ->getRepository('AppBundle:Customers')
                ->find($receivedCusId);

        if ($updateCustomersEntity) {
            $updateCustomersEntity->setName($receivedName);

            $em = $this->getDoctrine()->getManager();
            $em->persist($updateCustomersEntity);
            $em->flush();

            $results = array("success" => true, "message" => "Customer updated");

        } else {
            $logger->info('AppBundle\Controller\Desktop\ACCustomersController.php\updateCustomerAction() - Error: Customer does not exist');
            throw new \Exception("Error, Customer does not exist");
        }
        return new JsonResponse($results);
    }

    public function removeCustomerAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\ACCustomersController.php\removeCustomerAction() - Start');


        $receivedCusId = $inputParams['CUS_ID'];
        if (isset($receivedCusId) && intval($receivedCusId) > 0) {
            $customersEntity = $this->getDoctrine()
                ->getRepository('AppBundle:Customers')
                ->find($receivedCusId);

            $em = $this->getDoctrine()->getManager();
            $em->remove($customersEntity);
            $em->flush();
            $results = array("success" => true, "message" => "Customer deleted");
        } else {
            throw new \Exception("No customer selected");
        }
        return new JsonResponse($results);
    }

 }

