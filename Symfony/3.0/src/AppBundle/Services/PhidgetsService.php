<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class PhidgetsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
    }

    public function getPhidgetsPorts($configFile) {
        $this->logger->info('AppBundle\Services\PhidgetsService\getPhidgetsPorts() - Start');

        if (is_file($configFile)) {
            $phidgetsensors = array();
            array_push($phidgetsensors, array('ID' => 0, 'NAME' => 'Disabled'));
            $getconfig = parse_ini_file($configFile, FALSE, INI_SCANNER_RAW);
            foreach($getconfig as $key=>$value) {
                    $value = trim(str_replace("\"", "", $value));
                    if (substr($key, 0,20) == "cfgphidgetsensortype" && $key != "cfgphidgetsensortypenb") {
                            $srcfgparameters = explode(",", $value);
                            if($srcfgparameters[0][0] == " ") {$sensorName = substr($srcfgparameters[0],1);} else {$sensorName = $srcfgparameters[0];}
                            array_push($phidgetsensors, array('ID' => substr($key, 20,21), 'NAME' => $sensorName));
                    }
            }
            return $phidgetsensors;
        } else {
            return array("success" => false, "title" => "Source Access", "message" => "Unable to access global config file");                                                    
        }
    }

}
