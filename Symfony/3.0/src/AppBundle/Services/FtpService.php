<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use AppBundle\Services\ConfigurationService;


class FtpService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, ConfigurationService $configurationService) {
        $this->tokenStorage      = $tokenStorage;
        $this->em                   = $doctrine->getManager();
        $this->logger               = $logger;
        $this->connection           = $doctrine->getConnection();
        $this->doctrine             = $doctrine;
        $this->configurationService = $configurationService;
    }

    public function getServersFromConfigFile($configFile, $receivedSourceid = null) {
        $this->logger->info('AppBundle\Services\FtpService\getServersFromConfigFile() - File: ' . $configFile);
        if (is_file($configFile)) {
            $sourceConfigurationRaw = $this->configurationService->openConfigFile($configFile);
            $sourceconfigurationFTPServers = array();
            foreach($sourceConfigurationRaw as $key=>$value) {
                if ($key != "cfgftpserverslistnb") {
                    $value = trim(str_replace("\"", "", $value));
                    $serverID = str_replace('cfgftpserverslist', '', $key);
                    $serverParameters = explode(",", $value);
                    if($serverParameters[0][0] == " ") {$serverName = substr($serverParameters[0],1);} else {$serverName = $serverParameters[0];}
                    if($serverParameters[1][0] == " ") {$serverHost = substr($serverParameters[1],1);} else {$serverHost = $serverParameters[1];}
                    if($serverParameters[2][0] == " ") {$serverUsername = substr($serverParameters[2],1);} else {$serverUsername = $serverParameters[2];}
                    if($serverParameters[3][0] == " ") {$serverPassword = substr($serverParameters[3],1);} else {$serverPassword = $serverParameters[3];}
                    if($serverParameters[4][0] == " ") {$serverDirectory = substr($serverParameters[4],1);} else {$serverDirectory = $serverParameters[4];}
                    if($serverParameters[5][0] == " ") {$serverActive = substr($serverParameters[5],1);} else {$serverActive = $serverParameters[5];}
                    if($serverParameters[6][0] == " ") {$serverXferEnable = substr($serverParameters[6],1);} else {$serverXferEnable = $serverParameters[6];}
                    if($serverParameters[7][0] == " ") {$serverXferThreads = substr($serverParameters[7],1);} else {$serverXferThreads = $serverParameters[7];}
                    array_push($sourceconfigurationFTPServers, array(
                        'ID' => $serverID
                        , 'NAME' => $serverName
                        , 'HOST' => $serverHost
                        , 'USERNAME' => $serverUsername
                        , 'PASSWORD' => $serverPassword
                        , 'DIRECTORY' => $serverDirectory
                        , 'ACTIVE' => $serverActive
                        , 'XFERENABLE' => $serverXferEnable
                        , 'XFERTHREADS' => $serverXferThreads
                        , 'SOURCEID' => $receivedSourceid
                        )
                    );
                }
            }
            return $sourceconfigurationFTPServers;
        } else {
            return array("success" => false, "title" => "Source Access", "message" => "Unable to access source config file");                                        
        }
    }

    public function updateServersConfigFile($configFile, $sourceconfigurationFTPServers, $deleteRecordId = null) {
        $userEntity = $this->tokenStorage->getToken()->getUser();
        $this->logger->info('AppBundle\Services\FtpService\updateServersConfigFile() - File: ' . $configFile);

        $cfgftpserverslistnb = count($sourceconfigurationFTPServers);

        $f=fopen($configFile, "w");
         fwrite($f, "#EDIT: Last modified by " . $userEntity->getUsername() . " on " . date(DATE_RFC822) . "\n");
         fwrite($f, "cfgftpserverslistnb=" . $cfgftpserverslistnb . "\n");
         foreach ($sourceconfigurationFTPServers as $idx=>$ftpServer) {
             if ($ftpServer['ID'] != $deleteRecordId) {
                 $configName = "cfgftpserverslist" . $ftpServer['ID'];
                 $configValue = "\"" . $ftpServer['NAME'] . "\",\"" . $ftpServer['HOST'] . "\",\"" . $ftpServer['USERNAME'] . "\",\"" . $ftpServer['PASSWORD'] . "\",\"" . $ftpServer['DIRECTORY'] . "\",\"" . $ftpServer['ACTIVE']. "\",\"" . $ftpServer['XFERENABLE']. "\",\"" . $ftpServer['XFERTHREADS']. "\"";
                 fwrite($f, $configName . "=" . $configValue . "\n");
             }
         }
         fclose($f);
    }

    public function updateFtpServer($sourceconfigurationFTPServers, $inputParams) {
        $this->logger->info('AppBundle\Services\FtpService\updateFtpServer()');
        foreach ($sourceconfigurationFTPServers as $idx=>$ftpServer) {
            if ($ftpServer['ID'] == $inputParams['ID']) {
                $sourceconfigurationFTPServers[$idx]['NAME'] = $inputParams['NAME'];
                $sourceconfigurationFTPServers[$idx]['HOST'] = $inputParams['HOST'];
                $sourceconfigurationFTPServers[$idx]['USERNAME'] = $inputParams['USERNAME'];
                $sourceconfigurationFTPServers[$idx]['PASSWORD'] = $inputParams['PASSWORD'];
                $sourceconfigurationFTPServers[$idx]['DIRECTORY'] = $inputParams['DIRECTORY'];
                $sourceconfigurationFTPServers[$idx]['ACTIVE'] = $inputParams['ACTIVE'];
                $sourceconfigurationFTPServers[$idx]['XFERENABLE'] = $inputParams['XFERENABLE'];
                $sourceconfigurationFTPServers[$idx]['XFERTHREADS'] = $inputParams['XFERTHREADS'];
                $sourceconfigurationFTPServers[$idx]['SOURCEID'] = $inputParams['SOURCEID'];
            }            
        }
        return $sourceconfigurationFTPServers;
    }

    public function getFtpServerbyId($sourceconfigurationFTPServers, $serverId) {
        $this->logger->info('AppBundle\Services\FtpService\updateFtpServer()');
        $returnFtpServer = null;
        foreach ($sourceconfigurationFTPServers as $idx=>$ftpServer) {
            if ($ftpServer['ID'] == $serverId) {
                $returnFtpServer = $ftpServer;
            }
        }
        return $returnFtpServer;
    }

    public function getLastServerId($sourceconfigurationFTPServers) {
        $this->logger->info('AppBundle\Services\FtpService\updateFtpServer()');
        $lastId = 0;
        foreach ($sourceconfigurationFTPServers as $idx=>$ftpServer) {
            if ($ftpServer['ID'] >= $lastId) {
                $lastId = $ftpServer['ID'];
            }
        }
        return $lastId;
    }
    
    public function calculateFTPServerHash($sourceconfigurationFTPServers, $serverId) {
        $this->logger->info('AppBundle\Services\FtpService\calculateFTPServerHash()');
        # Hash is an md5 of the remote host and the username
        $identifiedFtpServer = null;
        foreach ($sourceconfigurationFTPServers as $idx=>$ftpServer) {
            if ($ftpServer['ID'] == $serverId) {
                $identifiedFtpServer = $ftpServer;
            }
        }
        return md5($identifiedFtpServer['HOST'] . $identifiedFtpServer['USERNAME']);        

    }    

}
