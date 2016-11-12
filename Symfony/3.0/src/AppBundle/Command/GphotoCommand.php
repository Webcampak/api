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

use Symfony\Component\Process\Process;

class GphotoCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:gphoto')
            ->setDescription('Perform various options on gphoto')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port to which the camera is currently connected')
            ->addOption('owner', null, InputOption::VALUE_REQUIRED, 'Owner to be assigned to the camera');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|                     GPHOTO COMMANDS                  |');
        self::log($output, 'info', '--------------------------------------------------------');

        $gphotoOwner = $input->getOption('owner');
        $gphotoPort = $input->getOption('port');
        if (isset($gphotoOwner)) {
            self::assignOwner($output, $gphotoPort, $gphotoOwner);
        }
        return 0;
    }

    protected function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }

    protected function assignOwner(OutputInterface $output, $gphotoPort, $gphotoOwner) {
        self::log($output, 'info', 'GphotoCommand.php\assignOwner() - Assign Owner to Gphoto-connected camera');
        self::runSystemProcess($output, 'GphotoCommand.php\assignOwner() - ', "gphoto2 -v --port " . $gphotoPort . " --set-config /main/settings/ownername=" . $gphotoOwner);
    }

    protected function runSystemProcess(OutputInterface $output, $message, $command) {
        self::log($output, 'info', $message . 'Running command: ' . $command);
        $createConfiguration = new Process($command);
        $createConfiguration->run();
        $processOutputLines = explode("\n", $createConfiguration->getOutput());
        foreach($processOutputLines as $processLine) {
            self::log($output, 'info', $message . 'Gphoto Subprocess output: ' . $processLine);
            return true;
        }
        if (!$createConfiguration->isSuccessful()) {
            self::log($output, 'error', $message . 'Unable to perform action');
            return false;
        }
    }

}

