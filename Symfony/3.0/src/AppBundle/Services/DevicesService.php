<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\Process\Process;

class DevicesService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, ConfigurationService $configurationService, $paramDirConfig) {
        $this->tokenStorage              = $tokenStorage;
        $this->em                           = $doctrine->getManager();
        $this->logger                       = $logger;
        $this->connection                   = $doctrine->getConnection();
        $this->doctrine                     = $doctrine;
        $this->configurationService         = $configurationService;
        $this->paramDirConfig               = $paramDirConfig;
    }

    public function getGphotoDir() {
        $this->logger->info('AppBundle\Services\DevicesService\getGphotoDir()');
        $configurationFile = $this->paramDirConfig . 'config-general.cfg';
        $generalConfig = $this->configurationService->openConfigFile($configurationFile);
        if (isset($generalConfig['cfggphotodir']) && $generalConfig['cfggphotodir'] != '') {
            return $generalConfig['cfggphotodir'];
        } else {
            throw new \Exception("Gphoto2 bin directory has not been set");
        }
    }

    public function getGphotoCapabilities() {
        $this->logger->info('AppBundle\Services\DevicesService\getGphotoCapabilities()');

        $outputtext = '';
        $process = new Process('gphoto2 --auto-detect');
        $process->run();
        foreach (explode("\n", $process->getOutput()) as $gphotoOutput) {
            if (strpos($gphotoOutput,'usb:') !== false) {
                $this->logger->info('AppBundle\Services\DevicesService\getGphotoCapabilities() - Gphoto Output:' . $gphotoOutput);
                preg_match("/usb:...,.../", $gphotoOutput, $gphotoOutputUsbPort);
                preg_match("/.+?(?=usb)/", $gphotoOutput, $gphotoOutputCameraName);
                
                if (isset($gphotoOutputCameraName[0])) {
                    $this->logger->info('AppBundle\Services\DevicesService\getGphotoCapabilities() - Gphoto Camera Name:' . $gphotoOutputCameraName[0]);
                }                
                
                if (isset($gphotoOutputUsbPort[0])) {
                    $this->logger->info('AppBundle\Services\DevicesService\getGphotoCapabilities() - Gphoto Port:' . $gphotoOutputUsbPort[0]);                    
                    $command = self::getGphotoDir() .  'gphoto2 --port ' . trim($gphotoOutputUsbPort[0]) . ' --abilities';
                    $getGphotoAbilities = new Process($command);
                    $getGphotoAbilities->run();
                    if (!$getGphotoAbilities->isSuccessful()) {
                        $this->logger->info('AppBundle\Services\DevicesService\getGphotoCapabilities(): Unable to access gphoto2');
                        return false;
                    }
                    $outputtext = $getGphotoAbilities->getOutput() . '\n';
                }                
            }
        }
        return $outputtext;
    }

    public function getGphotoList() {
        $this->logger->info('AppBundle\Services\DevicesService\getGphotoList()');

        $command = self::getGphotoDir() .  'gphoto2 --auto-detect';
        $getGphotoList = new Process($command);
        $getGphotoList->run();
        if (!$getGphotoList->isSuccessful()) {
            $this->logger->info('AppBundle\Services\DevicesService\getGphotoList(): Unable to access gphoto2');
            return false;
        }
        return $getGphotoList->getOutput();
    }

    public function getLsusb() {
        $this->logger->info('AppBundle\Services\DevicesService\getLsusb()');
        $getLsUsb = new Process('lsusb');
        $getLsUsb->run();
        if (!$getLsUsb->isSuccessful()) {
            $this->logger->info('AppBundle\Services\DevicesService\getLsusb(): Unable to access lsusb');
            return false;
        }
        return $getLsUsb->getOutput();
    }

    public function getDevices() {
        $this->logger->info('AppBundle\Services\DevicesService\getDevices()');

        $dbresults = array('GPHOTOLIST' => self::getGphotoList()
                , 'LSUSB' => self::getLsusb()
                , 'GPHOTOCAPABILITIES' => self::getGphotoCapabilities());

        $results['results'] = $dbresults;
        $results['total'] = count($dbresults);
        return $results;
    }
    
    public function getUsbPorts() {
        $this->logger->info('AppBundle\Services\DevicesService\getUsbPorts()');
        
        $detectedCameras = array();

        $process = new Process('gphoto2 --auto-detect');
        $process->run();
        foreach (explode("\n", $process->getOutput()) as $gphotoOutput) {
            if (strpos($gphotoOutput,'usb:') !== false) {
                $this->logger->info('AppBundle\Services\DevicesService\getUsbPorts() - Gphoto Output:' . $gphotoOutput);
                preg_match("/usb:...,.../", $gphotoOutput, $gphotoOutputUsbPort);
                preg_match("/.+?(?=usb)/", $gphotoOutput, $gphotoOutputCameraName);
                array_push($detectedCameras, array(
                    'ID' => trim($gphotoOutputUsbPort[0])
                    , 'NAME' => trim($gphotoOutputCameraName[0])
                    //, 'NAME' => trim($gphotoOutputCameraName[0]) . ' (' . $gphotoOutputUsbPort[0] . ')'
                ));
            }
        }
        return $detectedCameras;
    }    
    
}
