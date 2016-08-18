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
namespace AppBundle\Controller\Desktop\Stats;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatsController extends Controller {

    public function getSystemStatsAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\StatsController.php\getSystemStatsAction() - Start');

        $receivedRange = $inputParams['RANGE'];

        $results = $this->get('app.svc.stats')->getSystemStats($receivedRange);
        return new JsonResponse($results);
    }

    public function getSourcesPicturesCountSizeAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\StatsController.php\getSourcesPicturesCountSizeAction() - Start');

        $receivedSourceId = $inputParams['SOURCEID'];

        $sourcestats = $this->get('app.svc.stats')->getSourcesPicturesCountSize($receivedSourceId);
        $results['results'] = $sourcestats;
        $results['total'] = count($sourcestats);        
        return new JsonResponse($results);
    }

    public function getSourcesDiskUsageAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\StatsController.php\getSourcesDiskUsageAction() - Start');

        $receivedSourceId = $inputParams['SOURCEID'];

        $sourcestats = $this->get('app.svc.stats')->getSourcesDiskUsage($receivedSourceId);
        $results['results'] = $sourcestats;
        $results['total'] = count($sourcestats);
        
        return new JsonResponse($results);
    }



 }
