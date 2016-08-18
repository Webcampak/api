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

class ParseConfigCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:parseconf')
            ->setDescription('Parse Configuration')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|                 INITIALIZE DATABASE                  |');
        self::log($output, 'info', '--------------------------------------------------------');

        self::parseConfig($output);
    }

    function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }


    function parseConfig(OutputInterface $output) {
        self::log($output, 'comment', '*********');
        self::log($output, 'info', 'ParseConfigCommand.php\parseConfig() - Starting to parse config');

        $linesArray = file("/home/francois/NetBeansProjects/v3.0/src/init/etc/config-source-videopost.cfg", FILE_IGNORE_NEW_LINES);

        $sourceConfigurationRaw = parse_ini_file("/home/francois/NetBeansProjects/v3.0/src/init/etc/config-source-videopost.cfg", FALSE, INI_SCANNER_RAW);
        foreach($sourceConfigurationRaw as $key=>$value) {
            $value = trim(str_replace("\"", "", $value));
            $currentDescription = "";
            foreach($linesArray as $currentLine) {
                if (isset($currentLine[0]) && $currentLine[0] == "#" && strpos($currentLine,$key) !== false) {
                    $currentDescription = trim(str_replace("#", "", $currentLine));
                    $currentDescription = trim(str_replace($key, "", $currentDescription));
                    $currentDescription = trim(str_replace(":", "", $currentDescription));
                }
            }
            echo ', "' . $key . '":{"default": "' . $value . '",    "type": "alphanum", "description": "' . $currentDescription . '"}';
            echo "\n";
        }
    }
}

