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
use Symfony\Component\Console\Input\ArrayInput;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use AppBundle\Entities\Database\Applications;
use AppBundle\Entities\Database\Permissions;
use AppBundle\Entities\Database\Users;
use AppBundle\Entities\Database\Groups;
use AppBundle\Entities\Database\GroupsApplications;
use AppBundle\Entities\Database\GroupsPermissions;

use AppBundle\Entities\Database\Sources;
use AppBundle\Entities\Database\UsersSources;

use AppBundle\Command\SourceCreateCommand;
use AppBundle\Classes\BufferedOutput;

class DbInitCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:dbinit')
            ->setDescription('Initialise Database')
            ->addOption('preconfigure', null, InputOption::VALUE_NONE, 'If set, the system will pre-configure 3 sources');               
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|                 INITIALIZE DATABASE                  |');
        self::log($output, 'info', '--------------------------------------------------------');

        self::createApplications($output);
        self::createPermissions($output);
        self::createGroups($output);
        self::createRootUser($output);
        
        if ($input->getOption('preconfigure')) {
            
            //Fake the authentication mechanism to update configuration as root
            $searchRootUserEntity = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Users')->findOneByUsername('root');                        
            $token = new UsernamePasswordToken($searchRootUserEntity, null, "secured_area", $searchRootUserEntity->getRoles());            
            $this->getContainer()->get("security.token_storage")->setToken($token);

        //SOURCE 1
            self::createSources($output, 1, 'S1: Mon-Fri 09:00-18:00');
            $sourceCapture = array (
                array ('NAME' => 'cfgsourceactive','VALUE' => 'yes','SOURCEID' => 1)
                , array ('NAME' => 'cfgsourcetype','VALUE' => 'testpicture','SOURCEID' => 1)
                , array ('NAME' => 'cfgminimumcapturevalue','VALUE' => '30','SOURCEID' => 1)
                , array ('NAME' => 'cfgminimumcaptureinterval','VALUE' => 'seconds','SOURCEID' => 1)                
                , array ('NAME' => 'cfgcroncapturevalue','VALUE' => '5','SOURCEID' => 1)
                , array ('NAME' => 'cfgcroncaptureinterval','VALUE' => 'minutes','SOURCEID' => 1)
                , array ('NAME' => 'cfgcroncalendar','VALUE' => 'yes','SOURCEID' => 1)
                , array ('NAME' => 'cfgcronday1','VALUE' => 'yes,09,00,18,00','SOURCEID' => 1)
                , array ('NAME' => 'cfgcronday2','VALUE' => 'yes,09,00,18,00','SOURCEID' => 1)
                , array ('NAME' => 'cfgcronday3','VALUE' => 'yes,09,00,18,00','SOURCEID' => 1)
                , array ('NAME' => 'cfgcronday4','VALUE' => 'yes,09,00,18,00','SOURCEID' => 1)
                , array ('NAME' => 'cfgcronday5','VALUE' => 'yes,09,00,18,00','SOURCEID' => 1)
                , array ('NAME' => 'cfgcronday6','VALUE' => 'no,00,00,00,00','SOURCEID' => 1)
                , array ('NAME' => 'cfgcronday7','VALUE' => 'no,00,00,00,00','SOURCEID' => 1)
                , array ('NAME' => 'cfgemailalertreminder','VALUE' => '50','SOURCEID' => 1)
                , array ('NAME' => 'cfgphidgeterroractivate','VALUE' => 'true','SOURCEID' => 1)
                , array ('NAME' => 'cfgphidgetcameraport','VALUE' => '0','SOURCEID' => 1)
                , array ('NAME' => 'cfgcopymainenable','VALUE' => 'yes','SOURCEID' => 1)
                , array ('NAME' => 'cfgcopymainsourceid','VALUE' => '10','SOURCEID' => 1)
                , array ('NAME' => 'cfghotlinksize2','VALUE' => '','SOURCEID' => 1)
                , array ('NAME' => 'cfghotlinksize3','VALUE' => '','SOURCEID' => 1)
                , array ('NAME' => 'cfgpicwatermarkactivate','VALUE' => 'no','SOURCEID' => 1)
                );
           
            $confFile = $this->getContainer()->getParameter('dir_etc') . "config-source1.cfg";
            $confSettingsFile = $this->getContainer()->getParameter('sys_config') . "config-source.json";            
            foreach($sourceCapture as $param) {
                $result = $this->getContainer()->get('app.svc.configuration')->updateSourceConfiguration('127.0.0.1', $param['SOURCEID'], $param['NAME'], $param['VALUE'], $confFile, $confSettingsFile, $this->getContainer());
                self::log($output, 'info', 'Source Configuration: ' . $result['message']);                
            }
            
            $sourceVideo = array (
                array ('NAME' => 'cfgvideocodecH2641080pcreate','VALUE' => 'no','SOURCEID' => 1)
            );    
            $confFile = $this->getContainer()->getParameter('dir_etc') . "config-source1-video.cfg";
            $confSettingsFile = $this->getContainer()->getParameter('sys_config') . "config-source-video.json";            
            foreach($sourceVideo as $param) {
                $result = $this->getContainer()->get('app.svc.configuration')->updateSourceConfiguration('127.0.0.1', $param['SOURCEID'], $param['NAME'], $param['VALUE'], $confFile, $confSettingsFile, $this->getContainer());
                self::log($output, 'info', 'Source Configuration: ' . $result['message']);                
            }            
        
        //SOURCE 2
            self::createSources($output, 2, 'S2: Mon-Fri 18:00-09:00 and Weekend');
            $sourceCapture = array (
                array ('NAME' => 'cfgsourceactive','VALUE' => 'yes','SOURCEID' => 2)
                , array ('NAME' => 'cfgsourcetype','VALUE' => 'testpicture','SOURCEID' => 2)
                , array ('NAME' => 'cfgminimumcapturevalue','VALUE' => '30','SOURCEID' => 2)
                , array ('NAME' => 'cfgminimumcaptureinterval','VALUE' => 'seconds','SOURCEID' => 2)                
                , array ('NAME' => 'cfgcroncapturevalue','VALUE' => '30','SOURCEID' => 2)
                , array ('NAME' => 'cfgcroncaptureinterval','VALUE' => 'minutes','SOURCEID' => 2)
                , array ('NAME' => 'cfgcroncalendar','VALUE' => 'yes','SOURCEID' => 2)
                , array ('NAME' => 'cfgcronday1','VALUE' => 'yes,18,00,09,00','SOURCEID' => 2)
                , array ('NAME' => 'cfgcronday2','VALUE' => 'yes,18,00,09,00','SOURCEID' => 2)
                , array ('NAME' => 'cfgcronday3','VALUE' => 'yes,18,00,09,00','SOURCEID' => 2)
                , array ('NAME' => 'cfgcronday4','VALUE' => 'yes,18,00,09,00','SOURCEID' => 2)
                , array ('NAME' => 'cfgcronday5','VALUE' => 'yes,18,00,09,00','SOURCEID' => 2)
                , array ('NAME' => 'cfgcronday6','VALUE' => 'yes,00,00,00,00','SOURCEID' => 2)
                , array ('NAME' => 'cfgcronday7','VALUE' => 'yes,00,00,00,00','SOURCEID' => 2)
                , array ('NAME' => 'cfgemailalertreminder','VALUE' => '50','SOURCEID' => 2)
                , array ('NAME' => 'cfgphidgeterroractivate','VALUE' => 'true','SOURCEID' => 2)
                , array ('NAME' => 'cfgphidgetcameraport','VALUE' => '0','SOURCEID' => 2)
                , array ('NAME' => 'cfgcopymainenable','VALUE' => 'yes','SOURCEID' => 2)
                , array ('NAME' => 'cfgcopymainsourceid','VALUE' => '10','SOURCEID' => 2)
                , array ('NAME' => 'cfghotlinksize2','VALUE' => '','SOURCEID' => 2)
                , array ('NAME' => 'cfghotlinksize3','VALUE' => '','SOURCEID' => 2)
                , array ('NAME' => 'cfgpicwatermarkactivate','VALUE' => 'no','SOURCEID' => 2)
                );
           
            $confFile = $this->getContainer()->getParameter('dir_etc') . "config-source2.cfg";
            $confSettingsFile = $this->getContainer()->getParameter('sys_config') . "config-source.json";            
            foreach($sourceCapture as $param) {
                $result = $this->getContainer()->get('app.svc.configuration')->updateSourceConfiguration('127.0.0.1', $param['SOURCEID'], $param['NAME'], $param['VALUE'], $confFile, $confSettingsFile, $this->getContainer());
                self::log($output, 'info', 'Source Configuration: ' . $result['message']);                
            }
            
            $sourceVideo = array (
                array ('NAME' => 'cfgvideocodecH2641080pcreate','VALUE' => 'no','SOURCEID' => 2)
            );    
            $confFile = $this->getContainer()->getParameter('dir_etc') . "config-source2-video.cfg";
            $confSettingsFile = $this->getContainer()->getParameter('sys_config') . "config-source-video.json";            
            foreach($sourceVideo as $param) {
                $result = $this->getContainer()->get('app.svc.configuration')->updateSourceConfiguration('127.0.0.1', $param['SOURCEID'], $param['NAME'], $param['VALUE'], $confFile, $confSettingsFile, $this->getContainer());
                self::log($output, 'info', 'Source Configuration: ' . $result['message']);                
            }      
            
            self::createSources($output, 10, 'S10: Centralization');
            $sourceCapture = array (
                array ('NAME' => 'cfgsourceactive','VALUE' => 'yes','SOURCEID' => 10)
                , array ('NAME' => 'cfgsourcetype','VALUE' => 'wpak','SOURCEID' => 10)
                , array ('NAME' => 'cfgcapturedelay','VALUE' => '40','SOURCEID' => 10)
                , array ('NAME' => 'cfgcapturedelayinterval','VALUE' => 'seconds','SOURCEID' => 10)                
                , array ('NAME' => 'cfgcroncapturevalue','VALUE' => '10','SOURCEID' => 10)
                , array ('NAME' => 'cfgcroncaptureinterval','VALUE' => 'minutes','SOURCEID' => 10)
                , array ('NAME' => 'cfgcroncalendar','VALUE' => 'no','SOURCEID' => 10)
                , array ('NAME' => 'cfgemailalertreminder','VALUE' => '50','SOURCEID' => 10)
                , array ('NAME' => 'cfghotlinksize2','VALUE' => '','SOURCEID' => 10)
                , array ('NAME' => 'cfghotlinksize3','VALUE' => '','SOURCEID' => 10)
                , array ('NAME' => 'cfgpicwatermarkactivate','VALUE' => 'no','SOURCEID' => 10)
                );
           
            $confFile = $this->getContainer()->getParameter('dir_etc') . "config-source10.cfg";
            $confSettingsFile = $this->getContainer()->getParameter('sys_config') . "config-source.json";            
            foreach($sourceCapture as $param) {
                $result = $this->getContainer()->get('app.svc.configuration')->updateSourceConfiguration('127.0.0.1', $param['SOURCEID'], $param['NAME'], $param['VALUE'], $confFile, $confSettingsFile, $this->getContainer());
                self::log($output, 'info', 'Source Configuration: ' . $result['message']);                
            }
                           
        }

    }

    protected function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }

    protected function createApplications(OutputInterface $output) {
        self::log($output, 'comment', '*********');
        self::log($output, 'info', 'DbInitCommand.php\createApplications() - Adding Applications to Database');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $allApplications = $this->getContainer()->getParameter('applications');
        foreach($allApplications as $currentApplication) {
            $appCode = key($currentApplication);
            self::log($output, 'info', 'DbInitCommand.php\processBiDbInitQueue() - Processing: ' . key($currentApplication));

            $searchApplicationId = $this
                                    ->getContainer()
                                    ->get('doctrine')
                                    ->getRepository('AppBundle:Applications')
                                    ->findOneByName(key($currentApplication));
            if ($searchApplicationId) {
                self::log($output, 'comment', 'DbInitCommand.php\processBiDbInitQueue() - Skipping: ' . key($currentApplication) . ' already exists in database');
            } else {
                $newApplicationEntity = new Applications();
                $newApplicationEntity->setName($currentApplication[$appCode]['name']);
                $newApplicationEntity->setCode($appCode);
                $newApplicationEntity->setNotes($currentApplication[$appCode]['description']);
                $em = $this->getContainer()->get('doctrine')->getManager();
                $em->persist($newApplicationEntity);
            }
        }
        $em->flush();
    }

    protected function createPermissions(OutputInterface $output) {
        self::log($output, 'comment', '*********');
        self::log($output, 'info', 'DbInitCommand.php\createPermissions() - Adding Permissions to Database');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $allPermissions = $this->getContainer()->getParameter('permissions');
        foreach($allPermissions as $currentPermission) {
            self::log($output, 'info', 'DbInitCommand.php\processBiDbInitQueue() - Processing: ' . key($currentPermission));

            $searchPermissionId = $this
                ->getContainer()
                ->get('doctrine')
                ->getRepository('AppBundle:Permissions')
                ->findOneByName(key($currentPermission));
            if ($searchPermissionId) {
                self::log($output, 'comment', 'DbInitCommand.php\processBiDbInitQueue() - Skipping: ' . key($currentPermission) . ' already exists in database');
            } else {
                $newPermissionEntity = new Permissions();
                $newPermissionEntity->setName(key($currentPermission));
                $newPermissionEntity->setNotes(current($currentPermission));
                $em = $this->getContainer()->get('doctrine')->getManager();
                $em->persist($newPermissionEntity);
            }
        }
        $em->flush();
    }

    protected function createGroups(OutputInterface $output) {
        self::log($output, 'comment', '*********');
        self::log($output, 'info', 'DbInitCommand.php\createGroups() - Adding Groups to Database');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $allGroups = $this->getContainer()->getParameter('groups');
        foreach($allGroups as $currentGroup) {
            $groupName = key($currentGroup);
            self::log($output, 'info', 'DbInitCommand.php\processBiDbInitQueue() - Processing: ' . $groupName);
            self::log($output, 'info', 'DbInitCommand.php\processBiDbInitQueue() - Processing Description: ' . $currentGroup[$groupName]['description']);

            $searchGroupEntity = $this
                ->getContainer()
                ->get('doctrine')
                ->getRepository('AppBundle:Groups')
                ->findOneByName(key($currentGroup));
            if (!$searchGroupEntity) {
                $newGroupEntity = new Groups();
                $newGroupEntity->setName($groupName);
                $newGroupEntity->setNotes($currentGroup[$groupName]['description']);
                $em = $this->getContainer()->get('doctrine')->getManager();
                $em->persist($newGroupEntity);
            } else {
                self::log($output, 'comment', 'DbInitCommand.php\processBiDbInitQueue() - Group exists: ' . key($currentGroup) . ' already exists in database');
                $newGroupEntity = $searchGroupEntity;
            }

            foreach($currentGroup[$groupName]['applications'] as $currentGroupApplications) {
                self::log($output, 'info', 'DbInitCommand.php\processBiDbInitQueue() - Processing Application: ' . $currentGroupApplications);

                $applicationEntity = $this->getContainer()->get('doctrine')
                                        ->getRepository('AppBundle:Applications')
                                        ->findOneByCode($currentGroupApplications);

                $searchGroupApplicationEntity = $this->getContainer()->get('doctrine')
                                        ->getRepository('AppBundle:GroupsApplications')
                                        ->findOneBy(array('app' => $applicationEntity, 'gro' => $newGroupEntity));
                if (!$searchGroupApplicationEntity && $applicationEntity) {
                    $newGroupsApplicationsEntity = new GroupsApplications();
                    $newGroupsApplicationsEntity->setApp($applicationEntity);
                    $newGroupsApplicationsEntity->setGro($newGroupEntity);
                    $em->persist($newGroupsApplicationsEntity);
                } else {
                    self::log($output, 'comment', 'DbInitCommand.php\processBiDbInitQueue() - Skipping: ' . $currentGroupApplications . ' application already existing for this group');
                }

            }

            foreach($currentGroup[$groupName]['permissions'] as $currentGroupPermission) {
                self::log($output, 'info', 'DbInitCommand.php\processBiDbInitQueue() - Processing Permission: ' . $currentGroupPermission);

                $permissionEntity = $this->getContainer()->get('doctrine')
                                        ->getRepository('AppBundle:Permissions')
                                        ->findOneByName($currentGroupPermission);

                $searchGroupPermissionEntity = $this->getContainer()->get('doctrine')
                                        ->getRepository('AppBundle:GroupsPermissions')
                                        ->findOneBy(array('per' => $permissionEntity, 'gro' => $newGroupEntity));
                if (!$searchGroupPermissionEntity) {
                    $newGroupsPermissionsEntity = new GroupsPermissions();
                    $newGroupsPermissionsEntity->setPer($permissionEntity);
                    $newGroupsPermissionsEntity->setGro($newGroupEntity);
                    $em->persist($newGroupsPermissionsEntity);
                } else {
                    self::log($output, 'comment', 'DbInitCommand.php\processBiDbInitQueue() - Skipping: ' . $currentGroupPermission . ' permission already existing for this group');
                }
            }

        }
        $em->flush();
    }

    protected function createRootUser(OutputInterface $output) {
        self::log($output, 'comment', '*********');
        self::log($output, 'info', 'DbInitCommand.php\createRootUser() - Create Root User');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $adminGroupEntity = $this
            ->getContainer()
            ->get('doctrine')
            ->getRepository('AppBundle:Groups')
            ->findOneByName('Admin');

        $searchRootUserEntity = $this
                                ->getContainer()
                                ->get('doctrine')
                                ->getRepository('AppBundle:Users')
                                ->findOneByUsername('root');
        if ($searchRootUserEntity) {
            self::log($output, 'comment', 'DbInitCommand.php\processBiDbInitQueue() - User Root already exists, skipping');
        } else {
            $rootDefaultPassword = 'webcampak';
            $rootDefaultEmail = 'support@webcampak.com';

            $newUserEntity = new Users('root', 'webcampak', 'salt', array());
            $newUserEntity->setSalt(sha1($rootDefaultEmail . microtime()));

            //We generate a new encoded password
            $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($newUserEntity);
            $rootDefaultPasswordEncoded = $encoder->encodePassword($rootDefaultPassword, $newUserEntity->getSalt());

            self::log($output, 'info', 'DbInitCommand.php\createRootUser() - New encoded password is: ' . $rootDefaultPasswordEncoded);

            $newUserEntity->setPassword($rootDefaultPasswordEncoded);
            $newUserEntity->setChangePwdFlag('Y');
            $newUserEntity->setActiveFlag('Y');
            $newUserEntity->setFirstname('Root');
            $newUserEntity->setLastname('Root');
            $newUserEntity->setGro($adminGroupEntity);

            $this->getContainer()->get('doctrine')->getManager()->persist($newUserEntity);
            $this->getContainer()->get('doctrine')->getManager()->flush();
        }
    }

    protected function createSources(OutputInterface $output, $sourceId, $sourceName) {
        self::log($output, 'comment', '*********');
        self::log($output, 'info', 'DbInitCommand.php\createSources() - Create Source: ' . $sourceId . ' - ' . $sourceName);
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $searchSourceEntity = $this
                                ->getContainer()
                                ->get('doctrine')
                                ->getRepository('AppBundle:Sources')
                                ->findOneBySourceId($sourceId);
        if ($searchSourceEntity) {
            self::log($output, 'comment', 'DbInitCommand.php\createSources() - Source already exists, skipping');        
        } else {
            $sourceCreateCommand = new SourceCreateCommand();
            $sourceCreateCommand->setContainer($this->getContainer());
            $input = new ArrayInput(array('--sourceid' => $sourceId));
            $output = new BufferedOutput();
            $resultCode = $sourceCreateCommand->run($input, $output);
            $commandOutput = explode("\n", $output->getBuffer());
            foreach($commandOutput as $commandOutputLine) {
                self::log($output, 'info', 'DbInitCommand.php\createSources() - Console Subprocess: ' . $commandOutputLine);            
            }
            self::log($output, 'info', 'DbInitCommand.php\createSources() - Command Result code: ' . $resultCode);            

            $newSourceEntity = new Sources();
            $newSourceEntity->setName($sourceName);
            $newSourceEntity->setSourceId($sourceId);
            $newSourceEntity->setWeight($sourceId);

            $em->persist($newSourceEntity);

            $searchRootUserEntity = $this->getContainer()->get('doctrine')
                                ->getRepository('AppBundle:Users')
                                ->findOneByUsername('root');

            $newUsersSourceEntity = new UsersSources();
            $newUsersSourceEntity->setSou($newSourceEntity);
            $newUsersSourceEntity->setUse($searchRootUserEntity);
            $newUsersSourceEntity->setAlertsFlag('Y');
            $em->persist($newUsersSourceEntity);
            $em->flush();
        }
    }    
    
}

