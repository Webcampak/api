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
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use \Exception;

class ExtDirectRouterController extends Controller {
          
    public function indexAction() {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\ExtDirectRouterController.php\indexAction() - Start');

        $postData = trim(file_get_contents('php://input'));
        if (isset($postData)) {
            $extCall = json_decode($postData, true);
        } else {
            $logger->info('AppBundle\Controller\ExtDirectRouterController.php\indexAction() - ERRROR: Invalid request, dying as it does not make sense to push forward');
            throw new \Exception('Unable to decode request');
        }

        $logger->info('AppBundle\Controller\ExtDirectRouterController.php\indexAction() - Call: ' . json_encode($extCall));
                
        if (!isset($extCall['action'])) {// Means there is more than one single call in request
            //If Ext.Direct is making multiple calls simultaneously, an array of calls is received
            $logger->info('AppBundle\Controller\ExtDirectRouterController.php\indexAction() - Multi-Call request');
            $response = self::processMultiExtCall($extCall);
            
        } else {
            //Alternatively, Ext.Direct could make one single call
            $logger->info('AppBundle\Controller\ExtDirectRouterController.php\indexAction() - Single-Call request');
            $response = self::processSingleExtCall($extCall);
        }

        return new JsonResponse($response);
    }


    //http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
    private function isAssoc(array $array) {
      return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    private function processMultiExtCall($extCallBatch) {
        $logger = $this->get('logger');        
        $logger->info('AppBundle\Services\RouterService\processMultiExtCall() - Start');
        foreach ($extCallBatch as $idx=>$extCall) {
            $logger->info('AppBundle\Services\RouterService\processMultiExtCall() - Processing Ext Call: ' . $idx);
            $extCallBatch[$idx] = self::processSingleExtCall($extCall);
        }
        return $extCallBatch;
    }

    private function processSingleExtCall($extCall) {
        $logger = $this->get('logger');        
        $logger->info('AppBundle\Services\RouterService\processSingleExtCall() - Start');

        $callAction = $extCall['action'];
        $callMethod = $extCall['method'];

        if (self::checkMethodAction($callAction, $callMethod) === true) {
            // QUERY
            // {"action":"Sources","method":"getSources","data":[{"page":1,"start":0,"limit":25}],"type":"rpc","tid":16}

            // RESPONSE
            // {"type":"rpc","tid":16,"action":"Sources","method":"getSources","result":{"results":[{"SOU_ID":"1","NAME":"SOURCE 1","SOURCEID":"1","WEIGHT":"0","REMOTE_HOST":""},{"SOU_ID":"2","NAME":"source 2","SOURCEID":"2","WEIGHT":"2","REMOTE_HOST":""}],"total":2}
            return self::runAction($extCall);
        } else {
            throw new Exception("Insufficient privileges to access method: $callMethod on action $callAction");
        }

    }

    private function runAction($extCall) {
        $logger = $this->get('logger');        
        $logger->info('AppBundle\Services\RouterService\runAction() - Start');

        $callAction = $extCall['action'];
        $callMethod = $extCall['method'];
        $callData = $extCall['data'];
        
        $controllerFolder = $this->getParameter('extMethodsConfig')[$callAction]['folder'];
        $logger->info('AppBundle\Services\RouterService\runAction() - Folder: ' . $controllerFolder);

        if (isset($callData['data']) && isset($callData['data']['SOURCEID'])) {
            if ($this->get('app.svc.user')->hasCurrentUserAccessToSourceId($callData['data']['SOURCEID']) === false) {
                throw new Exception("Insufficient privileges to access source: ". $callData['data']['SOURCEID']);
            }
        }

        if (is_file($this->getParameter('kernel.root_dir') . '/../src/AppBundle/Controller/' . $controllerFolder . '/' . $callAction .'Controller.php')) {
            $logger->info('AppBundle\Services\RouterService\runAction() - Controller: ' . $controllerFolder . '/' . $callAction . ':' . $callMethod . ' - Input: ' . serialize($callData));

            //Handle multiple updates in a single call.
            //If associative, one single entity to be updated
            //If not associative, multiple entities to be updated (array of arrays)
            if (self::isAssoc($callData[0]) === true) {
                $jsonResponse = $this->forward('AppBundle:' . $controllerFolder . '/' . $callAction . ':' . $callMethod, array('inputParams'  => $callData[0]));
                $extCall['result'] = json_decode($jsonResponse->getContent(), true);
            } else {
                foreach($callData[0] as $inputParams) {
                    $jsonResponse = $this->forward('AppBundle:' . $controllerFolder . '/' . $callAction . ':' . $callMethod, array('inputParams'  => $inputParams));
                    $extCall['result'] = json_decode($jsonResponse->getContent(), true);
                }
            }

            $logger->info('AppBundle\Services\RouterService\runAction() - Controller: ' . $controllerFolder . '/' . $callAction . ':' . $callMethod . ' - Response: ' . serialize($extCall['result']));

            unset($extCall['data']);

            return $extCall;

        } else {
            $logger->info('AppBundle\Services\RouterService\runAction() - Controller does not exist, skipping ... ' . $this->getParameter('kernel.root_dir') . '/../src/AppBundle/Controller/' . $controllerFolder . '/' . $callAction);
            throw new Exception("Unable to locate controller related to access method: $callMethod on action $callAction");
        }
    }

    /*
     * Check if the method exist in configuration and if user is allowed to use it
     */
    private function checkMethodAction($callAction, $callMethod) {
        $logger = $this->get('logger');        
        $logger->info('AppBundle\Services\RouterService\checkMethodAction() - Start');
        
        if (!isset($this->getParameter('extMethodsConfig')[$callAction])) {
            $logger->info('AppBundle\Controller\ExtDirectRouterController.php\checkMethodAction() - ERROR: Call to undefined action: ' . $callAction);
            throw new Exception('Call to undefined action: ' . $callAction);
        } elseif (!isset($this->getParameter('extMethodsConfig')[$callAction]['methods'][$callMethod])) {
            $logger->info('AppBundle\Controller\ExtDirectRouterController.php\checkMethodAction() - ERROR: Call to undefined method: ' . $callMethod . ' on action ' . $callAction);
            throw new Exception("Call to undefined method: $callMethod on action $callAction");
        }

        return $this->get('app.svc.user')->isMethodAllowed($callAction, $callMethod, $this->getParameter('extMethodsConfig'));
    }
    
    
}
