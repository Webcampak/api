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
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Process\Process;

class SourceFTPCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:sourceftp')
            ->setDescription('Update ftp after source modification');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|                  UPDATE SOURCE FTP                  |');
        self::log($output, 'info', '--------------------------------------------------------');

        self::updateFTP($output);

        return 0;
    }

    function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }

    function updateFTP(OutputInterface $output) {
        self::log($output, 'info', 'SourceFTPCommand.php\updateFTP() - Update ftp for all sources');
        $wpakConfigDirectory = $this->getContainer()->getParameter('dir_etc');
        $wpakBinDirectory = $this->getContainer()->getParameter('dir_bin');

//        self::runSystemProcess($output, 'SourceFTPCommand.php\updateFTP() - ', "sudo python " . $wpakBinDirectory . "wpak-createlocalftpaccounts.py -g " . $wpakConfigDirectory . "config-general.cfg");
        self::runSystemProcess($output, 'SourceFTPCommand.php\updateFTP() - ', "sudo /usr/local/bin/webcampak system ftp");
    }

    function runSystemProcess(OutputInterface $output, $message, $command) {
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

