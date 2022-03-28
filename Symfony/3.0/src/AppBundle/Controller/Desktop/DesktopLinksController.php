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

class DesktopLinksController extends Controller {

    public function getLinksAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\LinksController.php\getLinksAction() - Start');

        $linksFile = $this->container->getParameter('sys_config') . "config-links.json";
        if (is_file($linksFile)) {
            $jsonContent = file_get_contents($linksFile);
            $linksArray = json_decode($jsonContent, true);
            $results = array();
            $results['results'] = $linksArray;
            $results['total'] = count($linksArray);
        } else {
            $results = array();
            $results['results'] = [];
            $results['total'] = count([]);
        }

        return new JsonResponse($results);
    }

 }
