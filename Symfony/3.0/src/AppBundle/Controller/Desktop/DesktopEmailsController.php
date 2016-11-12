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

class DesktopEmailsController extends Controller {

    public function getEmailsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopEmailsController.php\getEmailsAction() - Start');

        $dbresults = array();

        $results = array();
        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);

        return new JsonResponse($results);
    }

    public function sendEmailAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopEmailsController.php\sendEmailAction() - Start');

        $sendEmail = $this->get('app.svc.emails')->prepareEmailForQueue($inputParams);

        return new JsonResponse($sendEmail);
    }

    public function removeEmailAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\DesktopEmailsController.php\removeEmailAction() - Start');

        //Necessary to implement function

        $results = array("success" => true, "message" => "Modification done");

        return new JsonResponse($results);
    }


 }


