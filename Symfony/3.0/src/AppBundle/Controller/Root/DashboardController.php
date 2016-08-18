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
namespace AppBundle\Controller\Root;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard-{language}", defaults={"language" = "en_US.utf8"})
     */
     public function indexAction($language)
     {
            $logger = $this->get('logger');
            $logger->info('AppBundle\Controller\DashboardController.php\indexAction() - Start');

            if (is_file('../../../../build.txt')) {
                $currentBuild = file_get_contents('../../../../build.txt');
                $currentBuild = preg_replace('/[^(\x20-\x7F)]*/','', $currentBuild);
                $logger->info('AppBundle\Controller\DashboardController.php\indexAction() - Current Build: ' . $currentBuild);
            } else {
                $currentBuild = time();
                $logger->info('AppBundle\Controller\DashboardController.php\indexAction() - Current Build: ' . $currentBuild);
            }

          return $this->render('AppBundle:Dashboard:dashboard.html.php', array('currentBuild' => $currentBuild, 'language' => $language));
     }
}
