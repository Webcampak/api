<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use AppBundle\Controller\ExtDirectRouterController;

use \Exception;

class RouterService
{
    public $sourceController;

    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, UserService $userService, $extMethodsConfig, $kernelRootDir) {
        $this->tokenStorage  = $tokenStorage;
        $this->em               = $doctrine->getManager();
        $this->logger           = $logger;
        $this->connection       = $doctrine->getConnection();
        $this->doctrine         = $doctrine;
        $this->userService      = $userService;
        $this->extMethodsConfig = $extMethodsConfig;
        $this->kernelRootDir    = $kernelRootDir;
    }

    //http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
    public function isAssoc(array $array) {
      return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    public function processMultiExtCall($extCallBatch, ExtDirectRouterController $sourceController) {
        $this->logger->info('AppBundle\Services\RouterService\processMultiExtCall() - Start');
        foreach ($extCallBatch as $idx=>$extCall) {
            $this->logger->info('AppBundle\Services\RouterService\processMultiExtCall() - Processing Ext Call: ' . $idx);
            $extCallBatch[$idx] = self::processSingleExtCall($extCall, $sourceController);
        }
        return $extCallBatch;
    }

    public function processSingleExtCall($extCall, ExtDirectRouterController $sourceController) {
        $this->logger->info('AppBundle\Services\RouterService\processSingleExtCall() - Start');

        $callAction = $extCall['action'];
        $callMethod = $extCall['method'];

        $this->sourceController = $sourceController;

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

    public function runAction($extCall) {
        $this->logger->info('AppBundle\Services\RouterService\runAction() - Start');

        $callAction = $extCall['action'];
        $callMethod = $extCall['method'];
        $callData = $extCall['data'];

        $controllerFolder = $this->extMethodsConfig[$callAction]['folder'];
        $this->logger->info('AppBundle\Services\RouterService\runAction() - Folder: ' . $controllerFolder);

        if (isset($callData['data']) && isset($callData['data']['SOURCEID'])) {
            if ($this->userService->hasCurrentUserAccessToSourceId($callData['data']['SOURCEID']) === false) {
                throw new Exception("Insufficient privileges to access source: ". $callData['data']['SOURCEID']);
            }
        }

        if (is_file($this->kernelRootDir . '/../src/AppBundle/Controller/' . $controllerFolder . '/' . $callAction .'Controller.php')) {
            $this->logger->info('AppBundle\Services\RouterService\runAction() - Controller: ' . $controllerFolder . '/' . $callAction . ':' . $callMethod . ' - Input: ' . serialize($callData));

            //Handle multiple updates in a single call.
            //If associative, one single entity to be updated
            //If not associative, multiple entities to be updated (array of arrays)
            if (self::isAssoc($callData[0]) === true) {
                $jsonResponse = $this->sourceController->forward('AppBundle:' . $controllerFolder . '/' . $callAction . ':' . $callMethod, array('inputParams'  => $callData[0]));
                $extCall['result'] = json_decode($jsonResponse->getContent(), true);
            } else {
                foreach($callData[0] as $inputParams) {
                    $jsonResponse = $this->sourceController->forward('AppBundle:' . $controllerFolder . '/' . $callAction . ':' . $callMethod, array('inputParams'  => $inputParams));
                    $extCall['result'] = json_decode($jsonResponse->getContent(), true);
                }
            }

            $this->logger->info('AppBundle\Services\RouterService\runAction() - Controller: ' . $controllerFolder . '/' . $callAction . ':' . $callMethod . ' - Response: ' . serialize($extCall['result']));

            unset($extCall['data']);

            return $extCall;

        } else {
            $this->logger->info('AppBundle\Services\RouterService\runAction() - Controller does not exist, skipping ... ' . $this->kernelRootDir . '/../src/AppBundle/Controller/' . $controllerFolder . '/' . $callAction);
            throw new Exception("Unable to locate controller related to access method: $callMethod on action $callAction");
        }
    }

    /*
     * Check if the method exist in configuration and if user is allowed to use it
     */
    public function checkMethodAction($callAction, $callMethod) {
        $this->logger->info('AppBundle\Services\RouterService\checkMethodAction() - Start');

        if (!isset($this->extMethodsConfig[$callAction])) {
            $this->logger->info('AppBundle\Controller\ExtDirectRouterController.php\doRpc() - ERROR: Call to undefined action: ' . $callAction);
            throw new Exception('Call to undefined action: ' . $callAction);
        } elseif (!isset($this->extMethodsConfig[$callAction]['methods'][$callMethod])) {
            $this->logger->info('AppBundle\Controller\ExtDirectRouterController.php\doRpc() - ERROR: Call to undefined method: ' . $callMethod . ' on action ' . $callAction);
            throw new Exception("Call to undefined method: $callMethod on action $callAction");
        }

        return $this->userService->isMethodAllowed($callAction, $callMethod, $this->extMethodsConfig);
    }

}
