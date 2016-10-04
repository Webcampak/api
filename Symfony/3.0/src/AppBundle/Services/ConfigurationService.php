<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Console\Input\ArrayInput;

use AppBundle\Classes\BufferedOutput;

use AppBundle\Command\SourceCronCommand;
use AppBundle\Command\SourceFTPCommand;
use AppBundle\Command\GphotoCommand;

use AppBundle\Services\SourcesService;
use AppBundle\Services\UserService;
use AppBundle\Services\LogService;

class ConfigurationService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, SourcesService $sourceService, UserService $userService, LogService $logService, $jmsSerializer, $paramDirLog) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
        $this->sourceService   = $sourceService;
        $this->userService     = $userService;
        $this->logService      = $logService;
        $this->paramDirLog     = $paramDirLog;
        $this->jmsSerializer   = $jmsSerializer;
    }

    public function saveConfigurationChange($configFile, $configName, $configNewValue, $container) {
        $this->logger->info('AppBundle\Services\ConfigurationService\saveConfigurationChange() - Start');
        $this->logger->info('AppBundle\Services\ConfigurationService\saveConfigurationChange() - File: ' . $configFile);

        $userEntity =  $this->tokenStorage->getToken()->getUser();

        $convert = explode("\n", file_get_contents($configFile));
        $arraySize = count($convert);
        for ($i=0;$i<$arraySize;$i++) {
            if (strpos($convert[$i],'#EDIT:') !== false) {
                $convert[$i] = '#EDIT: Last modified by ' . $userEntity->getUsername() . ' on ' . date(DATE_RFC822);
            } else if (isset($convert[$i][0]) && $convert[$i][0] != "#") {
                $wecampakConf = explode("=",$convert[$i]);
                if (str_replace(' ', '', $wecampakConf[0]) == $configName) {
                    //Specific case if the config is a calendar day
                    if (strpos($configName, "cfgcronday") !== false) {$configNewValue = str_replace(',', '","', $configNewValue);}
                    if (strpos($configName, "cfgphidgetsensor") !== false && strlen($configName) === 17) {$configNewValue = str_replace(',', '","', $configNewValue);}
                    $convert[$i] = $configName . '="' . $configNewValue . '" ';
                }
            }
        }
        $f=fopen($configFile, "w");
        $arraySize = count($convert);
        for ($i=0;$i<$arraySize;$i++) {
            if ($convert[$i] != "") {
                fwrite($f, $convert[$i] . "\n");
            }
        }
        fclose($f);

        //In some situations additional actions need to be performed
        //Update GPHOTO Owner
        if ($configName == "cfgsourcegphotoowner") {
            $sourceConfigurationRaw = parse_ini_file($configFile, FALSE, INI_SCANNER_RAW);
            $gphotoCommand = new GphotoCommand();
            $gphotoCommand->setContainer($container);
            $input = new ArrayInput(array('--port' => $sourceConfigurationRaw['cfgsourcegphotocameraportdetail'], '--owner' => $configNewValue));
            $output = new BufferedOutput();
            $resultCode = $gphotoCommand->run($input, $output);
            $commandOutput = explode("\n", $output->getBuffer());
            foreach($commandOutput as $commandOutputLine) {
                $this->logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\saveConfigurationChange() - Gphoto2 owner Console Subprocess: ' . $commandOutputLine);
            }
            $this->logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\saveConfigurationChange() - Gphoto2 owner Command Result code: ' . $resultCode);
        }

        //Update Crontab
        if ($configName == "cfgcroncaptureinterval" || $configName == "cfgcroncapturevalue") {
            $sourceCronCommand = new SourceCronCommand();
            $sourceCronCommand->setContainer($container);
            $input = new ArrayInput(array());
            $output = new BufferedOutput();
            $resultCode = $sourceCronCommand->run($input, $output);
            $commandOutput = explode("\n", $output->getBuffer());
            foreach($commandOutput as $commandOutputLine) {
                $this->logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\saveConfigurationChange() - Cron update Console Subprocess: ' . $commandOutputLine);
            }
            $this->logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\saveConfigurationChange() - Cron update Command Result code: ' . $resultCode);
        }

        //Update FTP Accounts
        if ($configName == "cfglocalftppass") {
            $sourceFTPCommand = new SourceFTPCommand();
            $sourceFTPCommand->setContainer($container);
            $input = new ArrayInput(array());
            $output = new BufferedOutput();
            $resultCode = $sourceFTPCommand->run($input, $output);
            $commandOutput = explode("\n", $output->getBuffer());
            foreach($commandOutput as $commandOutputLine) {
                $this->logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\saveConfigurationChange() - FTP update Console Subprocess: ' . $commandOutputLine);
            }
            $this->logger->info('AppBundle\Controller\Desktop\AccesscontrolSourcesController.php\saveConfigurationChange() - FTP update Command Result code: ' . $resultCode);
        }
    }


    public function openConfigFile($configurationFile) {
        return parse_ini_file($configurationFile, FALSE, INI_SCANNER_RAW);
    }

    public function getSourceConfiguration($sourceId, $configurationFile, $configurationSettingsFile) {
        $userEntity = $this->tokenStorage->getToken()->getUser();

        if ($this->sourceService->isUserAllowed($sourceId)) {
            $userPermissions = $this->userService->getUserPermissions($userEntity);
            if (is_file($configurationFile)) {
                $sourceConfigurationRaw = self::openConfigFile($configurationFile);
                $configurationSettingsJson = file_get_contents($configurationSettingsFile);
                $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');

                $sourceconfigurationValues = array();
                foreach($sourceConfigurationRaw as $key=>$value) {
                    $parameterSetting = $configurationSettings->findParameterByName($key);
                    if ($parameterSetting !== false && $parameterSetting->getName() == $key) {
                        $this->logger->info('AppBundle\Services\ConfigurationService\getSourceConfiguration() - Parameter Name : ' . $parameterSetting->getName() . ' - Permission: ' . $parameterSetting->getPermission());
                        if (in_array($parameterSetting->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
                                $this->logger->info('AppBundle\Services\ConfigurationService\getSourceConfiguration() - Adding Parameter: ' . $key . ' Value: ' . $value);
                                $value = trim(str_replace("\"", "", $value));
                                array_push($sourceconfigurationValues, array('NAME' => $key, 'VALUE' => $value, 'SOURCEID' => $sourceId));
                        } else {
                            $this->logger->info('AppBundle\Services\ConfigurationService\getSourceConfiguration() - Parameter not allowed');
                        }
                    }
                }
                $results['results'] = $sourceconfigurationValues;
                $results['total'] = count($sourceconfigurationValues);
            } else {
            $results = array("success" => false, "title" => "Source Configuration", "message" => "Unable to access source config file");                
            }
        } else {
            $results = array("success" => false, "title" => "Access Denied", "message" => "You are not allowed to access this source");                        
        }
        return $results;
    }

    public function updateSourceConfiguration($clientIP, $sourceId, $receivedName, $receivedValue, $configurationFile, $configurationSettingsFile, $container) {
        $userEntity = $this->tokenStorage->getToken()->getUser();
        if ($this->sourceService->isUserAllowed($sourceId)) {
            $userPermissions = $this->userService->getUserPermissions($userEntity);
            if (is_file($configurationFile)) {
                $sourceConfigurationRaw = self::openConfigFile($configurationFile);

                $oldValue = trim(str_replace("\"", "", $sourceConfigurationRaw[$receivedName]));
                $configurationSettingsJson = file_get_contents($configurationSettingsFile);
                $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');
                $parameterSetting = $configurationSettings->findParameterByName($receivedName);
                if ($parameterSetting->getName() == $receivedName) {
                    $this->logger->info('AppBundle\Services\ConfigurationService\updateSourceConfiguration() - Parameter Name : ' . $parameterSetting->getName() . ' - Permission: ' . $parameterSetting->getPermission());
                    if (in_array($parameterSetting->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
                        $this->logService->logConfigurationChange(
                                $clientIP
                                , $sourceId
                                , $configurationFile
                                , $receivedName
                                , $receivedValue
                                , $oldValue);
                        self::saveConfigurationChange($configurationFile, $receivedName, $receivedValue, $container);
                        $results = array("success" => true, "message" => "Configuration successfully updated");
                    } else {
                        $results = array("success" => false, "message" => "User not allowed to edit parameter: " . $receivedName);
                    }
                }
            } else {
                $results = array("success" => false, "message" => "Unable to access source config file");
            }
        } else {
            $results = array("success" => false, "message" => "User not allowed to access source");
        }
        return $results;
    }

    public function getSectionConfiguration($configurationSettingsFile) {
        $userEntity = $this->tokenStorage->getToken()->getUser();

        $userPermissions = $this->userService->getUserPermissions($userEntity);

        $configurationSettingsJson = file_get_contents($configurationSettingsFile);
        $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');

        $sectionsAllowed = array();
        foreach($configurationSettings->getSections() as $section) {
            $this->logger->info('AppBundle\Controller\Desktop\SCCaptureController.php\getSectionConfiguration() - Section Name : ' . $section->getName() . ' - Permission: ' . $section->getPermission());
            if (in_array($section->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
                $this->logger->info('AppBundle\Controller\Desktop\SCCaptureController.php\getSectionConfiguration() - Adding Section: ' . $section->getName());
                array_push($sectionsAllowed, array('NAME' => $section->getName()));
            } else {
                $this->logger->info('AppBundle\Controller\Desktop\SCCaptureController.php\getSectionConfiguration() - Section not allowed');
            }
        }
        $results['results'] = $sectionsAllowed;
        $results['total'] = count($sectionsAllowed);

        return $results;
    }

    public function getConfigurationTabs($sysConfig, $userPermissions) {
        $this->logger->info('AppBundle\Services\ConfigurationService\configurationSettingsFile() - Start');

        $userEntity =  $this->tokenStorage->getToken()->getUser();        
        
        $tabsAllowed = array();
        $configurationSettingsJson = file_get_contents($sysConfig . "config-source.json");
        $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');
        if (in_array($configurationSettings->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
            array_push($tabsAllowed, array('NAME' => $configurationSettings->getName()));
        }
        $configurationSettingsJson = file_get_contents($sysConfig . "config-source-video.json");
        $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');
        if (in_array($configurationSettings->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
            array_push($tabsAllowed, array('NAME' => $configurationSettings->getName()));
        }
        $configurationSettingsJson = file_get_contents($sysConfig . "config-source-videocustom.json");
        $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');
        if (in_array($configurationSettings->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
            array_push($tabsAllowed, array('NAME' => $configurationSettings->getName()));
        }
        $configurationSettingsJson = file_get_contents($sysConfig . "config-source-videopost.json");
        $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');
        if (in_array($configurationSettings->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
            array_push($tabsAllowed, array('NAME' => $configurationSettings->getName()));
        }
        $configurationSettingsJson = file_get_contents($sysConfig . "config-source-ftpservers.json");
        $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');
        if (in_array($configurationSettings->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
            array_push($tabsAllowed, array('NAME' => $configurationSettings->getName()));
        }

        return $tabsAllowed;
    }


    public function getSystemConfiguration($configurationFile, $configurationSettingsFile) {
        $userEntity = $this->tokenStorage->getToken()->getUser();

        $userPermissions = $this->userService->getUserPermissions($userEntity);
        if (is_file($configurationFile)) {
            $sourceConfigurationRaw = self::openConfigFile($configurationFile);
            $configurationSettingsJson = file_get_contents($configurationSettingsFile);
            $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');

            $sourceconfigurationValues = array();
            foreach($sourceConfigurationRaw as $key=>$value) {
                $parameterSetting = $configurationSettings->findParameterByName($key);
                $this->logger->info('AppBundle\Services\ConfigurationService\getSystemConfiguration() - Parameter Key : ' . $key);
                $this->logger->info('AppBundle\Services\ConfigurationService\getSystemConfiguration() - Parameter Setting : ' . serialize($parameterSetting));
                if ($parameterSetting !== false && $parameterSetting->getName() == $key) {
                    $this->logger->info('AppBundle\Services\ConfigurationService\getSystemConfiguration() - Parameter Name : ' . $parameterSetting->getName() . ' - Permission: ' . $parameterSetting->getPermission());
                    if (in_array($parameterSetting->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
                            $this->logger->info('AppBundle\Services\ConfigurationService\getSystemConfiguration() - Adding Parameter: ' . $key . ' Value: ' . $value);
                            $value = trim(str_replace("\"", "", $value));
                            array_push($sourceconfigurationValues, array('NAME' => $key, 'VALUE' => $value));
                    } else {
                        $this->logger->info('AppBundle\Services\ConfigurationService\getSystemConfiguration() - Parameter not allowed');
                    }
                }
            }
            $results['results'] = $sourceconfigurationValues;
            $results['total'] = count($sourceconfigurationValues);
        } else {
            $results = array("success" => false, "title" => "Source Configuration", "message" => "Unable to access source config file");                            
        }
        return $results;
    }

    public function updateSystemConfiguration($clientIP, $receivedName, $receivedValue, $configurationFile, $configurationSettingsFile, $container) {
        $userEntity = $this->tokenStorage->getToken()->getUser();
        $userPermissions = $this->userService->getUserPermissions($userEntity);
        if (is_file($configurationFile)) {
            $sourceConfigurationRaw = self::openConfigFile($configurationFile);

            $oldValue = trim(str_replace("\"", "", $sourceConfigurationRaw[$receivedName]));
            $configurationSettingsJson = file_get_contents($configurationSettingsFile);
            $configurationSettings = $this->jmsSerializer->deserialize($configurationSettingsJson, 'AppBundle\Entities\Configuration\Configuration', 'json');
            $parameterSetting = $configurationSettings->findParameterByName($receivedName);
            if ($parameterSetting->getName() == $receivedName) {
                $this->logger->info('AppBundle\Services\ConfigurationService\updateSystemConfiguration() - Parameter Name : ' . $parameterSetting->getName() . ' - Permission: ' . $parameterSetting->getPermission());
                if (in_array($parameterSetting->getPermission(), $userPermissions) || $userEntity->getUsername() == 'root') {
                    $this->logService->logConfigurationChange(
                            $clientIP
                            , null
                            , $configurationFile
                            , $receivedName
                            , $receivedValue
                            , $oldValue);
                    self::saveConfigurationChange($configurationFile, $receivedName, $receivedValue, $container);
                    $results = array("success" => true, "message" => "Configuration successfully updated");
                } else {
                    $results = array("success" => false, "message" => "User not allowed to edit parameter: " . $receivedName);
                }
            }
        } else {
            $results = array("success" => false, "message" => "Unable to access source config file");
        }

        return $results;
    }
    
    public function getSourceConfigurationParameterValue($configurationFile, $parameter) {
        $this->logger->info('AppBundle\Services\ConfigurationService\getSourceConfigurationParameterValue() - Start');
        $this->logger->info('AppBundle\Services\ConfigurationService\getSourceConfigurationParameterValue() - File: ' . $configurationFile);
        if (is_file($configurationFile)) {
            $sourceConfigurationRaw = self::openConfigFile($configurationFile);
            foreach($sourceConfigurationRaw as $key=>$value) {
                if ($key == $parameter) {
                    $value = trim(str_replace("\"", "", $value));
                    $this->logger->info('AppBundle\Services\ConfigurationService\getSourceConfigurationParameterValue() - Key:  ' . $key . ' = ' . $value);                    
                    return $value;
                }
            }            
        }
    }    

}
