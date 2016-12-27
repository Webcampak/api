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

class SourceMoveCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:sourcemove')
            ->setDescription('Take an ID and delete a source with that ID')
            ->addOption('srcid', null, InputOption::VALUE_REQUIRED, 'Source Source ID to be copied from')
            ->addOption('dstid', null, InputOption::VALUE_REQUIRED, 'Destination Source ID to be moved to');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|                     MOVE SOURCCE                     |');
        self::log($output, 'info', '--------------------------------------------------------');

        $srcId = $input->getOption('srcid');
        $dstId = $input->getOption('dstid');

        self::log($output, 'info', 'Moving content from source ID: ' . $srcId . '  to source ID: ' . $dstId);
        if (self::checkSourceExists($output, $dstId) === false) {
            self::moveSourceContent($output, $srcId, $dstId);
            self::moveSourceConfiguration($output, $srcId, $dstId);
            return 0; // Return success
        } else {
            return 1; // Return failure
        }
    }

    protected function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }

    protected function moveSourceContent(OutputInterface $output, $srcId, $dstId) {
        self::log($output, 'info', 'SourceMoveCommand.php\moveSourceContent() - Move source directory to backup location');
        $sourcesDirectory = $this->getContainer()->getParameter('dir_sources');

        self::moveFile($output, $sourcesDirectory . 'source' . $srcId, $sourcesDirectory . 'source' . $dstId);
    }

    protected function moveSourceConfiguration(OutputInterface $output, $srcId, $dstId) {
        self::log($output, 'info', 'SourceMoveCommand.php\moveSourceConfiguration() - Move source configuration to backup location');
        $etcDirectory = $this->getContainer()->getParameter('dir_etc');

        self::moveFile($output, $etcDirectory . 'config-source' . $srcId . '.cfg',                $etcDirectory . 'config-source' . $dstId . '.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $srcId . '-video.cfg',          $etcDirectory . 'config-source' . $dstId . '-video.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $srcId . '-videocustom.cfg',    $etcDirectory . 'config-source' . $dstId . '-videocustom.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $srcId . '-videopost.cfg',      $etcDirectory . 'config-source' . $dstId . '-videopost.cfg');
        self::moveFile($output, $etcDirectory . 'config-source' . $srcId . '-ftpservers.cfg',     $etcDirectory . 'config-source' . $dstId . '-ftpservers.cfg');
    }

    protected function moveSourceLogs(OutputInterface $output, $sourceId) {
        self::log($output, 'info', 'SourceMoveCommand.php\moveSourceConfiguration() - Move source logs to backup location');
        $logsDirectory = $this->getContainer()->getParameter('dir_logs');
        self::moveFile($output, $logsDirectory . 'source' . $srcId, $logsDirectory . 'source' . $dstId);
    }

    protected function moveFile($output, $sourceFile, $destinationFile) {
        $fs = new Filesystem();
        if ($fs->exists($sourceFile)) {
            $fs->rename($sourceFile, $destinationFile);
            self::log($output, 'info', 'SourceMoveCommand.php\moveSourceConfiguration() - File/Directory moved from ' . $sourceFile . ' to ' . $destinationFile);
        } else {
            self::log($output, 'info', 'SourceMoveCommand.php\moveSourceConfiguration() - File/Directory:  ' . $sourceFile . ' does not exist, nothing to move');
        }
    }

    protected function checkSourceExists(OutputInterface $output, $sourceId) {
        self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Checking if source already exists');

        $wpakConfigDirectory = $this->getContainer()->getParameter('dir_etc');
        $sourcesDirectory = $this->getContainer()->getParameter('dir_sources');
        self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Configuration Directory: ' . $wpakConfigDirectory);

        $fs = new Filesystem();
        // Test if source configuration file exists
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '.cfg')) {
            self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-video.cfg')) {
            self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-video.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-videocustom.cfg')) {
            self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-videocustom.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-videopost.cfg')) {
            self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-videocustom.cfg' . ' already exists. Exiting ...');
            return true;
        }
        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '-ftpservers.cfg')) {
            self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '-videocustom.cfg' . ' already exists. Exiting ...');
            return true;
        }

        if ($fs->exists($wpakConfigDirectory . 'config-source' . $sourceId . '.cfg')) {
            self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - Config file: ' . 'config-source' . $sourceId . '.cfg' . ' already exists. Exiting ...');
            return true;
        }
        self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - No previous configuration files found');

        self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Source Directory: ' . $sourcesDirectory . 'source' . $sourceId . '/');
        if ($fs->exists($sourcesDirectory . 'source' . $sourceId . '/')) {
            self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Directory exists');
            self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Searching for .jpg');

            $finder = new Finder();
            $finder->in($sourcesDirectory . 'source' . $sourceId . '/');
            $finder->in($sourcesDirectory . 'source' . $sourceId . '/')->exclude('tmp');

            $finder->name('*.jpg');
            self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Number of jpg files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - There are jpg files in the sources directory, exiting');
                return true;
            }

            $finder->name('*.raw');
            self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Number of raw files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - There are raw files in the sources directory, exiting');
                return true;
            }

            $finder->name('*.avi');
            self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Number of avi files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - There are avi files in the sources directory, exiting');
                return true;
            }

            $finder->name('*.mp4');
            self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - Number of mp4 files found: ' . iterator_count($finder) / 2);
            if (iterator_count($finder) > 0) {
                self::log($output, 'error', 'SourceMoveCommand.php\checkSourceExists() - There are mp4 files in the sources directory, exiting');
                return true;
            }
        }
        self::log($output, 'info', 'SourceMoveCommand.php\checkSourceExists() - No previous content (jpg, raw, avi, mp4) found');
        return false;
    }

    protected function updateCron(OutputInterface $output) {
        self::log($output, 'info', 'SourceMoveCommand.php\updateCron() - Update crontab for all sources');
        self::runSystemProcess($output, 'SourceMoveCommand.php\updateCron() - ', "/usr/local/bin/webcampak system cron");
    }

    protected function updateFtpAccounts(OutputInterface $output) {
        self::log($output, 'info', 'SourceMoveCommand.php\updateFtpAccounts() - Update FTP accounts');
        self::runSystemProcess($output, 'SourceMoveCommand.php\updateFtpAccounts() - ', "sudo /usr/local/bin/webcampak system ftp");
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

