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

class SCFTPServersController extends Controller {

    public function getFTPServersAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCFTPServersController.php\getFTPServersAction() - Start');
        $logger->info('AppBundle\Controller\Desktop\SCFTPServersController.php\getFTPServersAction() - Serialize: ' . serialize($inputParams));

        if ($this->get('app.svc.sources')->isUserAllowed($inputParams['SOURCEID'])) {
            $configFile = $this->container->getParameter('dir_etc') . "config-source" . $inputParams['SOURCEID'] . "-ftpservers.cfg";
            $sourceconfigurationFTPServers = $this->get('app.svc.ftp')->getServersFromConfigFile($configFile, $inputParams['SOURCEID']);
            $results['results'] = $sourceconfigurationFTPServers;
            $results['total'] = count($sourceconfigurationFTPServers);
        } else {
                throw new \Exception("User not allowed to access source");
        }
        return new JsonResponse($results);
    }

    public function updateFTPServerAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCFTPServersController.php\updateFTPServerAction() - Start');

        if ($this->get('app.svc.sources')->isUserAllowed($inputParams['SOURCEID'])) {
            $configFile = $this->container->getParameter('dir_etc') . "config-source" . $inputParams['SOURCEID'] . "-ftpservers.cfg";
            if (is_file($configFile)) {
                $ftpService = $this->get('app.svc.ftp');
                //First we add all existing FTP servers into an array
                $sourceconfigurationFTPServers = $ftpService->getServersFromConfigFile($configFile, $inputParams['SOURCEID']);
                if (strpos($inputParams['ID'],'FTP_') !== false) {
                    //This server is the last, we automatically add it to the end of the array
                    $newFTPServer = array(
                        'ID' => $ftpService->getLastServerId($sourceconfigurationFTPServers) + 1
                        , 'NAME' => $inputParams['NAME']
                        , 'HOST' => $inputParams['HOST']
                        , 'USERNAME' => $inputParams['USERNAME']
                        , 'PASSWORD' => $inputParams['PASSWORD']
                        , 'DIRECTORY' => $inputParams['DIRECTORY']
                        , 'ACTIVE' => $inputParams['ACTIVE']
                        , 'XFERENABLE' => $inputParams['XFERENABLE']
                        , 'XFERTHREADS' => $inputParams['XFERTHREADS']
                        , 'SOURCEID' => $inputParams['SOURCEID']
                    );
                    array_push($sourceconfigurationFTPServers, $newFTPServer);
                    $results['results'] = $newFTPServer;
                } else {
                    $sourceconfigurationFTPServers = $ftpService->updateFtpServer($sourceconfigurationFTPServers, $inputParams);
                    $results['results'] = $ftpService->getFtpServerbyId($sourceconfigurationFTPServers, $inputParams['ID']);
                }
                $ftpService->updateServersConfigFile($configFile, $sourceconfigurationFTPServers, null);
                $results['total'] = 1;
            } else {
                $results = array("success" => false, "title" => "Source Access", "message" => "Unable to access source config file");                                        
            }
        } else {
            $results = array("success" => false, "title" => "Source Access", "message" => "User not allowed to access source config file");                                        
        }
        return new JsonResponse($results);
    }

    public function removeFTPServerAction($inputParams) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\Desktop\SCFTPServersController.php\removeFTPServerAction() - Start');

        if ($this->get('app.svc.sources')->isUserAllowed($inputParams['SOURCEID'])) {
            $configFile = $this->container->getParameter('dir_etc') . "config-source" . $inputParams['SOURCEID'] . "-ftpservers.cfg";
            $sourceconfigurationFTPServers = $this->get('app.svc.ftp')->getServersFromConfigFile($configFile, $inputParams['SOURCEID']);

            $this->get('app.svc.ftp')->updateServersConfigFile($configFile, $sourceconfigurationFTPServers, $inputParams['ID']);

            $sourceconfigurationFTPServers = $this->get('app.svc.ftp')->getServersFromConfigFile($configFile, $inputParams['SOURCEID']);

            $results['results'] = $sourceconfigurationFTPServers;
            $results['total'] = count($sourceconfigurationFTPServers);
        } else {
            $results = array("success" => false, "title" => "Source Access", "message" => "User not allowed to access source config file");                                        
        }
        return new JsonResponse($results);
    }

 }
