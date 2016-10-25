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

use Symfony\Component\Process\Process;

class SourceDeleteCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:sourcedelete')
            ->setDescription('Take an ID and delete a source with that ID')
            ->addOption('sourceid', null, InputOption::VALUE_REQUIRED, 'Source ID to be delete on the system');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|                    DELETE SOURCE                     |');
        self::log($output, 'info', '--------------------------------------------------------');

        $fs = new Filesystem();

        $sourceId = $input->getOption('sourceid');
        $resourcesDirectory = $this->getContainer()->getParameter('dir_resources');
        $targetDirectory = $resourcesDirectory . 'deleted/' . date('YmdHis') . '-source' . $sourceId . '/';

        self::log($output, 'info', 'Processing source ID: ' . $sourceId);

        if (!$fs->exists($resourcesDirectory . 'deleted/')) {$fs->mkdir($resourcesDirectory . 'deleted/', 0700 );}
        $fs->mkdir($targetDirectory, 0700 );
        $fs->mkdir($targetDirectory . 'etc', 0700 );
        $fs->mkdir($targetDirectory . 'logs', 0700 );

        self::moveSourceContent($output, $sourceId, $targetDirectory);
        self::moveSourceConfiguration($output, $sourceId, $targetDirectory);

        // Update Crontab
        self::updateCron($output);
        // Configure VSFTPD
        self::updateFtpAccounts($output);

        return 0;
    }

    protected function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }

    protected function moveSourceContent(OutputInterface $output, $sourceId, $targetDirectory) {
        self::log($output, 'info', 'SourceDeleteCommand.php\moveSourceContent() - Move source directory to backup location');
        $sourcesDirectory = $this->getContainer()->getParameter('dir_sources');

        $fs = new Filesystem();
        $fs->chmod($sourcesDirectory . 'source' . $sourceId, 0700, 0000, false);

        self::moveFile($output, $sourcesDirectory . 'source' . $sourceId, $targetDirectory . 'contents');
    }

    protected function moveSourceConfiguration(OutputInterface $output, $sourceId, $targetDirectory) {
        self::log($output, 'info', 'SourceDeleteCommand.php\moveSourceConfiguration() - Move source configuration to backup location');
        $etcDirectory = $this->getContainer()->getParameter('dir_etc');

        self::moveFile($output, $etcDirectory . 'config-source' . $sourceId . '.cfg',                $targetDirectory . 'etc/' . 'config-source' . $sourceId . '.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $sourceId . '-video.cfg',          $targetDirectory . 'etc/' . 'config-source' . $sourceId . '-video.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $sourceId . '-videocustom.cfg',    $targetDirectory . 'etc/' . 'config-source' . $sourceId . '-videocustom.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $sourceId . '-videopost.cfg',      $targetDirectory . 'etc/' . 'config-source' . $sourceId . '-videopost.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $sourceId . '-ftpservers.cfg',     $targetDirectory . 'etc/' . 'config-source' . $sourceId . '-ftpservers.cfg');
    }

    protected function moveSourceLogs(OutputInterface $output, $sourceId, $targetDirectory) {
        self::log($output, 'info', 'SourceDeleteCommand.php\moveSourceConfiguration() - Move source logs to backup location');

    }

    protected function moveFile($output, $sourceFile, $destinationFile) {
        $fs = new Filesystem();
        if ($fs->exists($sourceFile)) {
            $fs->rename($sourceFile, $destinationFile);
            self::log($output, 'info', 'SourceDeleteCommand.php\moveSourceConfiguration() - File/Directory moved from ' . $sourceFile . ' to ' . $destinationFile);
        } else {
            self::log($output, 'info', 'SourceDeleteCommand.php\moveSourceConfiguration() - File/Directory:  ' . $sourceFile . ' does not exist, nothing to move');
        }
    }

    protected function updateCron(OutputInterface $output) {
        self::log($output, 'info', 'SourceDeleteCommand.php\updateCron() - Update crontab for all sources');
        self::runSystemProcess($output, 'SourceDeleteCommand.php\updateCron() - ', "/usr/local/bin/webcampak system cron");
    }

    protected function updateFtpAccounts(OutputInterface $output) {
        self::log($output, 'info', 'SourceDeleteCommand.php\updateFtpAccounts() - Update FTP accounts');
        self::runSystemProcess($output, 'SourceDeleteCommand.php\updateFtpAccounts() - ', "sudo /usr/local/bin/webcampak system ftp");
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

