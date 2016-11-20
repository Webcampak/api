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
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Finder\Finder;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\Process\Process;

class SourceCreateCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:sourcecreate')
            ->setDescription('Take an ID and create a source with that ID')
            ->addOption('sourceid', null, InputOption::VALUE_REQUIRED, 'Source ID to be created on the system');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|                    CREATE SOURCCE                    |');
        self::log($output, 'info', '--------------------------------------------------------');


        $sourceId = $input->getOption('sourceid');
        self::log($output, 'info', 'Processing source ID: ' . $sourceId);
        self::log($output, 'info', 'Processing Current umask: ' . umask());
    
        if (self::checkSourceExists($output, $sourceId) === false) {
            self::deleteExistingSource($output, $sourceId);
                       
            // Prepare configuration
            self::prepareConfiguration($output, 'config-source.json', 'config-source' . $sourceId . '.cfg');
            self::prepareConfiguration($output, 'config-source-video.json', 'config-source' . $sourceId . '-video.cfg');
            self::prepareConfiguration($output, 'config-source-videocustom.json', 'config-source' . $sourceId . '-videocustom.cfg');
            self::prepareConfiguration($output, 'config-source-videopost.json', 'config-source' . $sourceId . '-videopost.cfg');
            self::prepareConfiguration($output, 'config-source-ftpservers.json', 'config-source' . $sourceId . '-ftpservers.cfg');

            // Prepare directories
            self::createSourceDirectories($output, $sourceId);

            // Configure VSFTPD
            self::updateFtpAccounts($output, $sourceId);

            // Update Crontab
            self::updateCron($output);
            
            // Fix any potential permission issues by setting correct permissions
            $fs = new Filesystem(); 
            $fs->chmod($this->getContainer()->getParameter('dir_sources') . "source" . $sourceId, 0700, 0000, true);
            $fs->chmod($this->getContainer()->getParameter('dir_sources') . "source" . $sourceId, 0500, 0000, false);

            return 0; // Return success
        } else {
            return 1; // Return failure
        }
    }

    protected function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }

    protected function deleteExistingSource(OutputInterface $output, $sourceId) {
        self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Delete existing source directory');
        $sourcesDirectory = $this->getContainer()->getParameter('dir_sources');
        $fs = new Filesystem();
        if ($fs->exists($sourcesDirectory . 'source' . $sourceId . '/')) {
            $fs->remove($sourcesDirectory . 'source' . $sourceId . '/');
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Source directory ' . $sourcesDirectory . 'source' . $sourceId . '/' . ' removed');
        } else {
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Source directory does not exist, nothing to delete');
        }
    }

    protected function checkSourceExists(OutputInterface $output, $sourceId) {
        self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Checking if source already exists');

        $wpakConfigDirectory = $this->getContainer()->getParameter('dir_etc');
        $sourcesDirectory = $this->getContainer()->getParameter('dir_sources');
        self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Configuration Directory: ' . $wpakConfigDirectory);

        $fs = new Filesystem();
        // Test if source configuration file exists
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '.cfg')) {
            self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-video.cfg')) {
            self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-video.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-videocustom.cfg')) {
            self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-videocustom.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-videopost.cfg')) {
            self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-videocustom.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-ftpservers.cfg')) {
            self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-videocustom.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '.cfg')) {
            self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '.cfg' . ' already exists. Exiting ...');
            return true;
        }
        self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - No previous configuration files found');

        self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Source Directory: ' . $sourcesDirectory . 'source' . $sourceId . '/');
        if ($fs->exists($sourcesDirectory . 'source' . $sourceId . '/')) {
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Directory exists');
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Searching for .jpg');

            $finder = new Finder();
            $finder->in($sourcesDirectory . 'source' . $sourceId . '/');
            $finder->in($sourcesDirectory . 'source' . $sourceId . '/')->exclude('tmp');

            $finder->name('*.jpg');
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Number of jpg files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - There are jpg files in the sources directory, exiting');
                return true;
            }

            $finder->name('*.raw');
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Number of raw files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - There are raw files in the sources directory, exiting');
                return true;
            }

            $finder->name('*.avi');
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Number of avi files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - There are avi files in the sources directory, exiting');
                return true;
            }

            $finder->name('*.mp4');
            self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - Number of mp4 files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceCreateCommand.php\checkSourceExists() - There are mp4 files in the sources directory, exiting');
                return true;
            }
        }
        self::log($output, 'info', 'SourceCreateCommand.php\checkSourceExists() - No previous content (jpg, raw, avi, mp4) found');
        return false;
    }

    protected function prepareConfigurationYaml(OutputInterface $output, $configFileSource, $configFileOutput) {
        self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - Prepare webcampak YAML Configuration');
        $sysConfigDirectory = $this->getContainer()->getParameter('sys_config');
        $wpakConfigDirectory = $this->getContainer()->getParameter('dir_etc');

        $fs = new Filesystem();
        if ($fs->exists($sysConfigDirectory . $configFileSource)) {
            self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - Processing configuration file ' . $sysConfigDirectory . $configFileSource);
            $configArray = array();
            $json = file_get_contents($sysConfigDirectory . $configFileSource);
            $configuration = $this->getContainer()->get('jms_serializer')->deserialize($json, 'AppBundle\Entities\Configuration\Configuration', 'json');
            file_put_contents($wpakConfigDirectory . $configFileOutput, '#CREATE: file created on ' . date(DATE_RFC822), FILE_APPEND | LOCK_EX);  
            file_put_contents($wpakConfigDirectory . $configFileOutput, "\n", FILE_APPEND | LOCK_EX);            
            foreach($configuration->getSections() as $section) {
                self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - Processing section: ' . $section->getName());
                foreach($section->getParameters() as $parameter) {
                    if ($parameter->getName() == "cfglocalftppass") {
                        //Generate default FTP password
                        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $newPassword = '';
                        for ($i = 0; $i < 10; $i++) {
                            $newPassword .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $paramValue = $newPassword;
                    } else {
                        if ($parameter->getType() == 'list') {
                            self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - This parameter is a list: ' . $section->getName());
                            $paramValue = array();
                            foreach($parameter->getValues() as $valueItem) {
                                $valueName = $valueItem->getName();
                                $paramValue[$valueName] = $valueItem->getDefault();                                
                            }
                        } else {
                            $paramValue = $parameter->getDefault();                            
                        }
                    }
                    $currentParamName = $parameter->getName();
                    $configArray[$currentParamName] = $paramValue;                                        
                }
            }
            file_put_contents($wpakConfigDirectory . $configFileOutput, Yaml::dump($configArray, 1), FILE_APPEND | LOCK_EX);            
            return true;
        } else {
            self::log($output, 'error', 'SourceCreateCommand.php\prepareConfiguration() - Unable to find configuration file: ' . $sysConfigDirectory . '_test-config-source.json');
            return false;
        }
    }

    protected function prepareConfiguration(OutputInterface $output, $configFileSource, $configFileOutput) {
        self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - Prepare webcampak Configuration');
        $sysConfigDirectory = $this->getContainer()->getParameter('sys_config');
        $wpakConfigDirectory = $this->getContainer()->getParameter('dir_etc');

        $fs = new Filesystem();
        if ($fs->exists($sysConfigDirectory . $configFileSource)) {
            self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - Processing configuration file ' . $sysConfigDirectory . $configFileSource);
            $json = file_get_contents($sysConfigDirectory . $configFileSource);
            $configuration = $this->getContainer()->get('jms_serializer')->deserialize($json, 'AppBundle\Entities\Configuration\Configuration', 'json');
            file_put_contents($wpakConfigDirectory . $configFileOutput, '#EDIT: Created on ' . date(DATE_RFC822), FILE_APPEND | LOCK_EX);
            foreach($configuration->getSections() as $section) {
                self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - Processing section: ' . $section->getName());
                file_put_contents($wpakConfigDirectory . $configFileOutput, "\n", FILE_APPEND | LOCK_EX);
                file_put_contents($wpakConfigDirectory . $configFileOutput, "# Section: " . $section->getName() ."\n", FILE_APPEND | LOCK_EX);
                file_put_contents($wpakConfigDirectory . $configFileOutput, "# -------------------------------------------\n", FILE_APPEND | LOCK_EX);
                foreach($section->getParameters() as $parameter) {
                    file_put_contents($wpakConfigDirectory . $configFileOutput, "# " . $parameter->getName() .": " . $parameter->getDescription() . "\n", FILE_APPEND | LOCK_EX);
                }
                foreach($section->getParameters() as $parameter) {
                    self::log($output, 'info', 'SourceCreateCommand.php\prepareConfiguration() - Processing parameter: ' . $parameter->getName());

                    if ($parameter->getName() == "cfglocalftppass") {
                        //Generate default FTP password
                        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $newPassword = '';
                        for ($i = 0; $i < 10; $i++) {
                            $newPassword .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $paramValue = $newPassword;
                    } else {
                        if ($parameter->getValues() !== null ) {
                            $paramValue = '';
                            foreach($parameter->getValues() as $value) {
                                if ($paramValue  != '') {$paramValue = $paramValue . ',';}
                                $paramValue = $paramValue . '"' . $value->getDefault() . '"';
                            }
                        } else {
                            $paramValue = '"' . $parameter->getDefault() . '"';
                        }
                    }
                    file_put_contents($wpakConfigDirectory . $configFileOutput, $parameter->getName() . "=" . $paramValue ."\n", FILE_APPEND | LOCK_EX);
                }
            }
            return true;
        } else {
            self::log($output, 'error', 'SourceCreateCommand.php\prepareConfiguration() - Unable to find configuration file: ' . $sysConfigDirectory . '_test-config-source.json');
            return false;
        }
    }

    protected function createSourceDirectories(OutputInterface $output, $sourceId) {
        self::log($output, 'info', 'SourceCreateCommand.php\createDirectories() - Create Source Directories');
        $wpakSourcesDirectory = $this->getContainer()->getParameter('dir_sources');

        $fs = new Filesystem();
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId, 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/pictures", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/tmp", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/live", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/videos", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/stats", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/audio", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/watermark", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/alerts", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/alerts/incidents", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/reports", 0700);
        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/capture", 0700);
//        $fs->mkdir($wpakSourcesDirectory . "source" . $sourceId . "/resources/sync-reports", 0700); #sync-reports are not located in the source directory anymore
    }

    protected function updateFtpAccounts(OutputInterface $output, $sourceId) {
        self::log($output, 'info', 'SourceCreateCommand.php\updateFtpAccounts() - Update FTP accounts');
        self::runSystemProcess($output, 'SourceCreateCommand.php\updateFtpAccounts() - ', "sudo /usr/local/bin/webcampak system ftp");
    }

    protected function updateCron(OutputInterface $output) {
        self::log($output, 'info', 'SourceCronCommand.php\updateCron() - Update crontab for all sources');
        self::runSystemProcess($output, 'SourceCreateCommand.php\updateCron() - ', "/usr/local/bin/webcampak system cron");
    }

    protected function runSystemProcess(OutputInterface $output, $message, $command) {
        self::log($output, 'info', $message . 'Running command: ' . $command);
        $createConfiguration = new Process($command);
        $createConfiguration->run();
        $processOutputLines = explode("\n", $createConfiguration->getOutput());
        foreach($processOutputLines as $processLine) {
            self::log($output, 'info', $message . 'Python Subprocess: ' . $processLine);
            return true;
        }
        if (!$createConfiguration->isSuccessful()) {
            self::log($output, 'error', $message . 'Unable to perform action');
            return false;
        }
    }

}

