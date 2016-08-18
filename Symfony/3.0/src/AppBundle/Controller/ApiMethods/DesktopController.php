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
namespace AppBundle\Controller\ApiMethods;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DesktopController extends Controller {

    public function indexAction() {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\ApiMethods\DesktopController.php\indexAction() - Start');

        $extMethodsConfig = $this->container->getParameter('extMethodsConfig');
        $actions = array();
        foreach ($extMethodsConfig as $aname => &$a) {
            $methods = array();
            foreach ($a['methods'] as $mname => &$m) {
                if (isset($m['len'])) {
                    $md = array(
                         'name' => $mname,
                         'len' => $m['len']
                    );
                } else {
                    $md = array(
                         'name' => $mname,
                         'params' => $m['params']
                    );
                }
                if (isset($m['formHandler']) && $m['formHandler']) {
                    $md['formHandler'] = true;
                }
                $methods[] = $md;
            }
            $actions[$aname] = $methods;
        }
        $logger->info('AppBundle\Controller\ApiMethods\DesktopController.php\indexAction() - Processing Completed, displaying results');

        //Build Symfony Response
        $responseVar = 'console.log("Log: Load: Symfony: Getting a list of all API methods available");';
        $responseVar .= 'var Ext_app_REMOTING_API = ';
        $cfg = array(
             'url' => 'router/desktop',
             'type' => 'remoting',
             'actions' => $actions
        );
        $responseVar .= json_encode($cfg);
        $responseVar .= ';';
        $responseVar .= 'console.log(Ext_app_REMOTING_API);';
        $responseVar .= 'console.log("Log: Load: Symfony: Call to api.php completed");';

        $response = new Response($responseVar);
        $response->headers->set('Content-Type', 'application/javascript');

        return $response;
    }
}



