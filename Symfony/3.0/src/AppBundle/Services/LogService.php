<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class LogService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, $paramDirLog) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
        $this->paramDirLog     = $paramDirLog;
    }

    public function logConfigurationChange($clientIP, $sourceId, $configFile, $configName, $configNewValue, $configOldValue) {
        $this->logger->info('AppBundle\Services\LogService\logConfigurationChange() - Start');
        $userEntity = $this->tokenStorage->getToken()->getUser();
        $this->logger->info('AppBundle\Services\LogService\logConfigurationChange() - Parameter: ' . $configName . ' Old Value: ' . $configOldValue . ' New Value: ' . $configNewValue);

        $logLine = array(
            'DATE' => date(DATE_RFC822)
            , 'USERNAME' => $userEntity->getUsername()
            , 'IP' => $clientIP
            , 'TYPE' => 'CONFIG'
            , 'FILE' => $configFile
            , 'PARAMETER' => $configName
            , 'OLD' => $configOldValue
            , 'NEW' => $configNewValue
        );

        if ($sourceId === null) {$logFile = 'edit-config-general-';}
        else {$logFile = '/source' . $sourceId . '/edit-source-';}
        
        file_put_contents($this->paramDirLog . $logFile . date("Y-m-d") . ".log", json_encode($logLine, JSON_FORCE_OBJECT) . "\r\n", FILE_APPEND | LOCK_EX);
    }

}
