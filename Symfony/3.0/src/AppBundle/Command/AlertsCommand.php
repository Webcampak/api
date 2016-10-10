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

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AlertsCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:alerts')
            ->setDescription('Monitor source for potential schedule alerts')
        ;
    }

    protected function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }
        
    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '|    MONITOR SOURCES FOR POSSIBLE SCHEDULE ALERTS      |');
        self::log($output, 'info', '--------------------------------------------------------');

        //Fake the authentication mechanism and act as root
        $searchRootUserEntity = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Users')->findOneByUsername('root');                        
        $token = new UsernamePasswordToken($searchRootUserEntity, null, "secured_area", $searchRootUserEntity->getRoles());            
        $this->getContainer()->get("security.token_storage")->setToken($token);        
        
        // Array to be used when sending emails to users
        $sourceAlerts = array();        
        
        // Get list of sources
        $availableSources = $this->getContainer()
                                ->get('doctrine')
                                ->getRepository('AppBundle:Sources')->findAll();

        // First part is dealing with data collection
        foreach ($availableSources as $sourceEntity) {
            self::log($output, 'info', 'AlertsCommand.php\execute() ---------------------------------------------------------------------------------');                        
            self::log($output, 'info', 'AlertsCommand.php\execute() - Processing Source: ' . $sourceEntity->getSourceId());  
            $sourceHasSchedule = $this->getContainer()->get('app.svc.sources')->checkSourceScheduleExists($sourceEntity->getSourceId());
            $sourceIsActive = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceEntity->getSourceId() . '.cfg', 'cfgsourceactive');            
            $latestPictureFile = $this->getContainer()->get('app.svc.pictures.directory')->getLatestPictureForSource($sourceEntity->getSourceId());                        
            if ($sourceHasSchedule !== false && $sourceIsActive == 'yes' && $latestPictureFile != '') {
                self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' -  Source has a schedule, is active and has previously captured pictures');            
                
                $sourceTimezone = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceEntity->getSourceId() . '.cfg', 'cfgcapturetimezone');
                $sourceAlertEmail = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceEntity->getSourceId() . '.cfg', 'cfgemailalertfailure');
                $sourceAlertEmailReminder = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceEntity->getSourceId() . '.cfg', 'cfgemailalertreminder');
                                                
                self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Source Timezone is: ' . $sourceTimezone);                            
                self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Source Alert user if delay greated than: ' . $sourceAlertEmail . ' Minutes');                            
                self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Source Send a reminder every: ' . $sourceAlertEmailReminder . ' Minutes');                            
                                
                $currentDate = new \DateTime('now', new \DateTimeZone($sourceTimezone));                
                $lastPictureDate = \DateTime::createFromFormat('YmdHis', substr($latestPictureFile, 0,14), new \DateTimeZone($sourceTimezone));                                                
                $nextCaptureFromPictureDate = $this->getContainer()->get('app.svc.schedule')->getNextCaptureSlot($sourceHasSchedule, $lastPictureDate, $output->isDebug());
                
                self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Current date is _______________________: ' . $currentDate->format('Y-m-d H:i:s'));                            
                self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Latest picture was captured at ________: ' . $lastPictureDate->format('Y-m-d H:i:s'));                            
                self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Following capture is/was scheduled for : ' . $nextCaptureFromPictureDate->format('Y-m-d H:i:s'));                            
                
                // Difference in minutes between current date and last time a picture was expected to be captured
                $diffInMinutes = round(($currentDate->getTimestamp() - $nextCaptureFromPictureDate->getTimestamp())/60);
                
                $incidentFile = null;
                if ($nextCaptureFromPictureDate >= $currentDate) {
                    self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - All good, the following capture should happen in the future'); 
                    $alertStatus = 'good';
                } else  {
                    self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Picture acquisition is late by ' . $diffInMinutes . ' minutes');                       
                    if ($diffInMinutes >= intval($sourceAlertEmail)) {
                        self::log($output, 'info', 'AlertsCommand.php\execute() - ' . $sourceEntity->getSourceId() . ' - Source has been late by more than ' . $sourceAlertEmail . ' minutes'); 
                        $alertStatus = 'error';
                        $incidentFile = substr($latestPictureFile, 0,14) . '.jsonl';
                    } else {
                        $alertStatus = 'late';
                    }
                }

                $alertsFile = $this->getContainer()->getParameter('dir_sources') . 'source' . $sourceEntity->getSourceId() . '/resources/alerts/' . substr($latestPictureFile, 0,8) . '.jsonl';    
                
                $alertArray = array(
                    'sourceid' => $sourceEntity->getSourceId()                    
                    , 'status' => $alertStatus
                    , 'previousStatus' => self::checkPreviousStatus($output, $alertsFile)
                    , 'currentDate' => $currentDate->format('c')
                    , 'lastPictureFile' => $latestPictureFile
                    , 'lastPictureDate' => $lastPictureDate->format('c')
                    , 'lastScheduledDate' => $nextCaptureFromPictureDate->format('c')
                    , 'captureLateBy' => $diffInMinutes
                    , 'sendAlertAfter' => $sourceAlertEmail
                    , 'sendReminderAfter' => $sourceAlertEmailReminder
                    , 'incidentFile' => $incidentFile
                );
                
                file_put_contents($alertsFile, json_encode($alertArray) . "\n", FILE_APPEND);
                
                $sourceAlerts[$sourceEntity->getSourceId()] = $alertArray;
            }
        }
        
        // Second part is dealing with sending users an alert        
        self::processUSerAlerts($output, $sourceAlerts);
                        
    }

    protected function processUSerAlerts(OutputInterface $output, $sourceAlerts) {
        self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts()');
        $users = $this->getContainer()->get('app.svc.alerts')->getUsersSourcesWithAlertsFlag();        
        foreach($users as $currentUserSource) {
            self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts() - Processing User: ' . $currentUserSource['EMAIL']);     
            $currentSourceId = $currentUserSource['SOURCEID'];                     
            if (isset($sourceAlerts[$currentSourceId])) {
                $incidentsFile = $this->getContainer()->getParameter('dir_sources') . 'source' . $currentUserSource['SOURCEID'] . '/resources/alerts/incidents/' . $sourceAlerts[$currentSourceId]['incidentFile'];                                        
                self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts() - Incidents File (if needed): ' . $incidentsFile);                            
                if ($sourceAlerts[$currentSourceId]['status'] == 'error' && intval($sourceAlerts[$currentSourceId]['sendAlertAfter']) > 0)  {
                    self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts() - ' . $currentUserSource['EMAIL'] . ' - Processing source: ID: ' . $currentUserSource['SOURCEID'] . ' - Name: ' . $currentUserSource['SOURCENAME']);     
                    if (!is_file($incidentsFile)) {
                        self::sendIncidentEmail($output, $sourceAlerts[$currentSourceId],  $currentUserSource['EMAIL'], $currentUserSource['SOURCENAME'], $incidentsFile);                        
                    } else {
                        $incidentEmailLog = file($incidentsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $logArray = array();
                        foreach($incidentEmailLog as $logLine) {
                            $logLine = json_decode($logLine, true);
                            if ($logLine['emailTo'] == $currentUserSource['EMAIL']) {
                                array_push($logArray, $logLine);
                            }
                        }
                        $logArray = array_reverse($logArray);
                        if (count($logArray) == 0) {
                            self::sendIncidentEmail($output, $sourceAlerts[$currentSourceId],  $currentUserSource['EMAIL'], $currentUserSource['SOURCENAME'], $incidentsFile);
                        } else {
                            $lastEmailDate = \DateTime::createFromFormat(\DateTime::ISO8601,$logArray[0]['currentDate']);
                            $sourceTimezone = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $currentUserSource['SOURCEID'] . '.cfg', 'cfgcapturetimezone');                            
                            $currentDate = new \DateTime('now', new \DateTimeZone($sourceTimezone));

                            // Difference in minutes between current date and last time a picture was expected to be captured
                            $diffInMinutes = round(($currentDate->getTimestamp() - $lastEmailDate->getTimestamp())/60);       
                            self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts() - ' . $currentUserSource['EMAIL'] . ' - Processing source: ID: ' . $currentUserSource['SOURCEID'] . ' Time since last reminder: ' . $diffInMinutes . 'mn');                                                                 
                            if ($diffInMinutes >= $sourceAlerts[$currentSourceId]['sendReminderAfter'] &&  intval($sourceAlerts[$currentSourceId]['sendReminderAfter']) > 0) {
                                self::sendIncidentEmail($output, $sourceAlerts[$currentSourceId],  $currentUserSource['EMAIL'], $currentUserSource['SOURCENAME'], $incidentsFile);                                
                            } else {
                                self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts() - ' . $currentUserSource['EMAIL'] . ' - Processing source: ID: ' . $currentUserSource['SOURCEID'] . ' Not enough time since last capture, not sending reminder');                                     
                            }
                        }
                    }
                } else if ($sourceAlerts[$currentSourceId]['status'] == 'good' && $sourceAlerts[$currentSourceId]['previousStatus'] == 'error') {
                    self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts() - ' . $currentUserSource['EMAIL'] . ' - Source is recovering after an error, source ID: ' . $currentUserSource['SOURCEID'] . ' - Name: ' . $currentUserSource['SOURCENAME']);
                    self::sendRecoveryEmail($output, $sourceAlerts[$currentSourceId],  $currentUserSource['EMAIL'], $currentUserSource['SOURCENAME'], $incidentsFile);                                            
                } else {
                    self::log($output, 'info', 'AlertsCommand.php\processUSerAlerts() - ' . $currentUserSource['EMAIL'] . ' - No Error on source ID: ' . $currentUserSource['SOURCEID'] . ' - Name: ' . $currentUserSource['SOURCENAME'] . ' or email alerts disabled');
                }  
            }                 
        }         
    }
    
    protected function checkPreviousStatus(OutputInterface $output, $alertsFile) {
        self::log($output, 'info', 'AlertsCommand.php\checkPreviousStatus()');
        if (!is_file($alertsFile)) {
            return null;            
        } else {
            $alertsLog = file($alertsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logArray = array();
            foreach($alertsLog as $logLine) {
                $logLine = json_decode($logLine, true);
                array_push($logArray, $logLine);
            }
            $logArray = array_reverse($logArray);
            if (isset($logArray[0]['status'])) {
                return $logArray[0]['status'];
            } else {
                return null;
            }            
        }        
    }
    
    protected function sendIncidentEmail(OutputInterface $output, $incidentArray, $userEmail, $sourceName, $incidentsFile) {
        self::log($output, 'info', 'AlertsCommand.php\sendIncidentEmail()');

        $sourceLocale = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $incidentArray['sourceid'] . '.cfg', 'cfgsourcelanguage');
        $lastCaptureDate = \DateTime::createFromFormat(\DateTime::ISO8601,$incidentArray['lastPictureDate']);
        $currentDate = \DateTime::createFromFormat(\DateTime::ISO8601,$incidentArray['currentDate']);
                
        $templateSubjectFile = $this->getContainer()->getParameter('dir_locale') . $sourceLocale . '/emails/captureAlertSubject.txt';
        if (!file_exists($templateSubjectFile)) {$templateSubjectFile = $this->getContainer()->getParameter('dir_locale') . 'en_US.utf8/emails/captureAlertSubject.txt';}
        $templateContentFile = $this->getContainer()->getParameter('dir_locale') . $sourceLocale . '/emails/captureAlertContent.txt';
        if (!file_exists($templateContentFile)) {$templateContentFile = $this->getContainer()->getParameter('dir_locale') . 'en_US.utf8/emails/captureAlertContent.txt';}

        $emailSubject = file_get_contents($templateSubjectFile);
        $emailSubject = str_replace("#CURRENTHOSTNAME#", gethostname(), $emailSubject);        
        $emailSubject = str_replace("#CURRENTSOURCENAME#", $sourceName, $emailSubject);        
        $emailSubject = str_replace("#CURRENTSOURCEID#", $incidentArray['sourceid'], $emailSubject);        

        $emailBody = file_get_contents($templateContentFile);
        $emailBody = str_replace("#TIMESINCELASTCAPTURE#", $incidentArray['captureLateBy'], $emailBody);        
        $emailBody = str_replace("#SENDALERTAFTER#", $incidentArray['sendAlertAfter'], $emailBody);        
        $emailBody = str_replace("#SENDREMINDERAFTER#", $incidentArray['sendReminderAfter'], $emailBody);        
        $emailBody = str_replace("#SOURCENAME#", $sourceName, $emailBody);        
        $emailBody = str_replace("#SOURCEID#", $incidentArray['sourceid'], $emailBody);   
        $emailBody = str_replace("#LASTSUCCESSFULCAPTUREDATE#", $lastCaptureDate->format('Y-m-d H:i:s'), $emailBody);        
        $emailBody = str_replace("#CURRENTSERVERTIME#", $currentDate->format('Y-m-d H:i:s'), $emailBody);        

        $emailsParams = array(
            'EMAIL_FROM' => $this->getContainer()->getParameter('mailer_from')
            , 'EMAIL_TO' => $userEmail
            , 'EMAIL_CC' => ''
            , 'SUBJECT' => $emailSubject
            , 'BODY' => $emailBody
            , 'ATTACHMENT_PATH' => ''
            , 'ATTACHMENT_NAME' => ''
            , 'ATTACHMENT_SOURCEID' => ''
        );
        
        $this->getContainer()->get('app.svc.emails')->prepareEmailForQueue($emailsParams);
        
        $incidentEmailLog = array(
            'sourceid' => $incidentArray['sourceid']
            , 'currentDate' => $currentDate->format('c')                
            , 'emailTo' => $userEmail
        );       
        self::log($output, 'info', 'AlertsCommand.php\sendIncidentEmail() - IncidentEmailLog: ' . $incidentsFile);        
        file_put_contents($incidentsFile, json_encode($incidentEmailLog) . "\n", FILE_APPEND);        
    }
    
    protected function sendRecoveryEmail(OutputInterface $output, $incidentArray, $userEmail, $sourceName, $incidentsFile) {
        self::log($output, 'info', 'AlertsCommand.php\sendRecoveryEmail()');

        $sourceLocale = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $incidentArray['sourceid'] . '.cfg', 'cfgsourcelanguage');
        $lastCaptureDate = \DateTime::createFromFormat(\DateTime::ISO8601,$incidentArray['lastPictureDate']);
        $currentDate = \DateTime::createFromFormat(\DateTime::ISO8601,$incidentArray['currentDate']);
                
        $templateSubjectFile = $this->getContainer()->getParameter('dir_locale') . $sourceLocale . '/emails/captureRecoveredSubject.txt';
        if (!file_exists($templateSubjectFile)) {$templateSubjectFile = $this->getContainer()->getParameter('dir_locale') . 'en_US.utf8/emails/captureRecoveredSubject.txt';}
        $templateContentFile = $this->getContainer()->getParameter('dir_locale') . $sourceLocale . '/emails/captureRecoveredContent.txt';
        if (!file_exists($templateContentFile)) {$templateContentFile = $this->getContainer()->getParameter('dir_locale') . 'en_US.utf8/emails/captureRecoveredContent.txt';}

        $emailSubject = file_get_contents($templateSubjectFile);
        $emailSubject = str_replace("#CURRENTHOSTNAME#", gethostname(), $emailSubject);        
        $emailSubject = str_replace("#CURRENTSOURCENAME#", $sourceName, $emailSubject);        
        $emailSubject = str_replace("#CURRENTSOURCEID#", $incidentArray['sourceid'], $emailSubject);        

        $emailBody = file_get_contents($templateContentFile);
        $emailBody = str_replace("#SOURCENAME#", $sourceName, $emailBody);        
        $emailBody = str_replace("#SOURCEID#", $incidentArray['sourceid'], $emailBody);   
        $emailBody = str_replace("#LASTSUCCESSFULCAPTUREDATE#", $lastCaptureDate->format('Y-m-d H:i:s'), $emailBody);        
        $emailBody = str_replace("#CURRENTSERVERTIME#", $currentDate->format('Y-m-d H:i:s'), $emailBody);        

        $emailsParams = array(
            'EMAIL_FROM' => $this->getContainer()->getParameter('mailer_from')
            , 'EMAIL_TO' => $userEmail
            , 'EMAIL_CC' => ''
            , 'SUBJECT' => $emailSubject
            , 'BODY' => $emailBody
            , 'ATTACHMENT_PATH' => ''
            , 'ATTACHMENT_NAME' => ''
            , 'ATTACHMENT_SOURCEID' => ''
        );
        
        $this->getContainer()->get('app.svc.emails')->prepareEmailForQueue($emailsParams);
    }    
    
    protected function countNumberSourcesErrors(OutputInterface $output, $userSources, $sourceAlerts) {
        self::log($output, 'info', 'AlertsCommand.php\countNumberSourcesErrors()');                
        $errorCount = 0;
        foreach ($sourceAlerts as $alert) {
            if ($alert['status'] == 'error') {
                foreach ($userSources as $source) {   
                    if ($source['SOURCEID'] == $alert['sourceid']) {
                        $errorCount++;
                    }
                }                             
            }            
        }
        return $errorCount;
    }
 
}

