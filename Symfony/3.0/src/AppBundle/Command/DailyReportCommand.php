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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DailyReportCommand extends ContainerAwareCommand
{
    protected function configure() {
        $this
            ->setName('wpak:dailyreport')
            ->setDescription('Generate a capture report for the previous day or for a specific day')
            ->addArgument('day', InputArgument::OPTIONAL, 'Process report for a specific day (YYYYMMDD), ALL to generate for all')
        ;
    }

    protected function log(OutputInterface $output, $level, $message) {
        $output->writeln('<' . $level . '>' .  date('m/d/Y h:i:s a', time()) . ' | ' . $message . '</' . $level . '>');
    }
        
    protected function execute(InputInterface $input, OutputInterface $output) {
        self::log($output, 'info', '--------------------------------------------------------');
        self::log($output, 'info', '| GENERATE A DAILY CAPTURE REPORT AND SEND IT BY EMAIL |');
        self::log($output, 'info', '--------------------------------------------------------');

        //Fake the authentication mechanism and act as root
        $searchRootUserEntity = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Users')->findOneByUsername('root');                        
        $token = new UsernamePasswordToken($searchRootUserEntity, null, "secured_area", $searchRootUserEntity->getRoles());            
        $this->getContainer()->get("security.token_storage")->setToken($token);        
        
        $reportDay = $input->getArgument('day');
        
        if ($reportDay != 'ALL') {
            $sourceDailyReport = self::generateReports($output, $reportDay);      
            
            // Second part is dealing with going through those reports and sending users an alert, only for a single day though.               
            self::parseReports($output, $sourceDailyReport);            
        } else {
            $serverTimezone = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_config') . 'config-general.cfg', 'cfgservertimezone');
            
            $firstPictureDay = $this->getContainer()->get('app.svc.pictures.directory')->getFirstPictureDayAmongstAllSources();                        
            self::log($output, 'info', 'execute() - First picture day: ' . $firstPictureDay);
            $currentDay = new \DateTime('now', new \DateTimeZone($serverTimezone));
            $reportDateYmd = $currentDay->format('Ymd');            
            while ($reportDateYmd >= $firstPictureDay) {
                self::log($output, 'info', 'execute() - Current Date: ' . $reportDateYmd);                
                $sourceDailyReport = self::generateReports($output, $reportDateYmd);      
                $currentDay->sub(new \DateInterval('P1D')); 
                $reportDateYmd = $currentDay->format('Ymd');                
            }
        }                       
    }

    protected function generateReports(OutputInterface $output, $reportDay) {
        self::log($output, 'info', 'DailyReportCommand.php\generateReports()');
        
        // Array to be used when sending emails to users
        $sourceDailyReport = array();        
        
        // Get list of sources
        $availableSources = $this->getContainer()
                                ->get('doctrine')
                                ->getRepository('AppBundle:Sources')->findAll();

        // First part is dealing with data collection
        foreach ($availableSources as $sourceEntity) {
            self::log($output, 'info', 'DailyReportCommand.php\generateReports() ---------------------------------------------------------------------------------');                        
            self::log($output, 'info', 'DailyReportCommand.php\generateReports() - Processing Source: ' . $sourceEntity->getSourceId());  
            $scheduleArray = $this->getContainer()->get('app.svc.sources')->checkSourceScheduleExists($sourceEntity->getSourceId());
            $sourceIsActive = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceEntity->getSourceId() . '.cfg', 'cfgsourceactive');            
            $latestPictureFile = $this->getContainer()->get('app.svc.pictures.directory')->getLatestPictureForSource($sourceEntity->getSourceId());                        
            if (/*$scheduleArray !== false && $sourceIsActive == 'yes' && */$latestPictureFile != '') {
                self::log($output, 'info', 'DailyReportCommand.php\generateReports() - ' . $sourceEntity->getSourceId() . ' - Source has a schedule, is active and has previously captured pictures');

                $sourceTimezone = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceEntity->getSourceId() . '.cfg', 'cfgcapturetimezone');                 
                //Identify Report Day
                if (!is_int($reportDay) || intval($reportDay) == 0) {
                    //Identify day to process
                    $reportDay = new \DateTime('now', new \DateTimeZone($sourceTimezone));
                    $reportDay->sub(new \DateInterval('P1D'));
                    $reportDateYmd = $reportDay->format('Ymd');
                } else {
                    self::log($output, 'info', 'DailyReportCommand.php\generateReports() - ' . $sourceEntity->getSourceId() . ' - Report Day: ' . $reportDay);                    
                    $reportDay = \DateTime::createFromFormat('Ymd', $reportDay, new \DateTimeZone($sourceTimezone));
                    $reportDateYmd = $reportDay->format('Ymd');                    
                }
                if (intval($reportDateYmd) > 0) {
                    self::log($output, 'info', 'DailyReportCommand.php\generateReports() - ' . $sourceEntity->getSourceId() . ' - Will run a report on day: ' . $reportDateYmd);

                    // Get array of captured pictures
                    $jpgDirectory = $this->getContainer()->getParameter('dir_sources') . 'source' . $sourceEntity->getSourceId() . '/pictures/' . $reportDateYmd . '/';
                    $capturedJpgs = array();
                    if (is_dir($jpgDirectory)) {
                        $capturedJpgs = $this->getContainer()->get('app.svc.pictures.directory')->listPicturesInDirectory($jpgDirectory, 'jpg', 'size');                                            
                    }
                    $rawDirectory = $this->getContainer()->getParameter('dir_sources') . 'source' . $sourceEntity->getSourceId() . '/pictures/raw/' . $reportDateYmd . '/';
                    $capturedRaws = array();
                    if (is_dir($rawDirectory)) {
                        $capturedRaws = $this->getContainer()->get('app.svc.pictures.directory')->listPicturesInDirectory($rawDirectory, 'jpg', 'size');                                            
                    }       
                    self::log($output, 'info', 'DailyReportCommand.php\generateReports() - ' . $sourceEntity->getSourceId() . ' - Output: ' . serialize($capturedJpgs));
                    
                    $sourceProcessRaw = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $sourceEntity->getSourceId() . '.cfg', 'cfgprocessraw');                                        
                    
                    $reportComparison = self::compareCapturedReportWithCaptureSchedule($output, $capturedJpgs, $capturedRaws, $scheduleArray, $reportDay, $sourceProcessRaw);
                    
                    $sourceReport = array_merge(array(
                            'sourceId' => $sourceEntity->getSourceId()
                            , 'sourceName' => $sourceEntity->getName()
                            , 'reportDay' => $reportDay->format('Y-m-d')
                            , 'active' => $sourceIsActive
                        ), $reportComparison);

                    self::log($output, 'info', 'DailyReportCommand.php\generateReports() - ' . $sourceEntity->getSourceId() . ' - Report Comparison: Serialize: ' . serialize($sourceReport));

                    $sourceReportFile = $this->getContainer()->getParameter('dir_sources') . 'source' . $sourceEntity->getSourceId() . '/resources/reports/' . $reportDateYmd . '.json';
                    file_put_contents($sourceReportFile, json_encode($sourceReport, JSON_FORCE_OBJECT));                    
                    
                    $sourceDailyReport[$sourceEntity->getSourceId()] = $sourceReport;                    
                } else {
                    self::log($output, 'info', 'DailyReportCommand.php\generateReports() - ' . $sourceEntity->getSourceId() . ' - There was no picutres captured yesterday, cancelling ... ');                    
                }
            }
        } 
        return $sourceDailyReport;
    }

    protected function parseReports(OutputInterface $output, $sourceDailyReport) {
        self::log($output, 'info', 'DailyReportCommand.php\parseReports()');
        $users = $this->getContainer()->get('app.svc.alerts')->getSingleUsersSourcesWithAlertsFlag();        
        foreach($users as $currentUser) {
            self::log($output, 'info', 'DailyReportCommand.php\parseReports() - Processing: ' . $currentUser['EMAIL']);
            $emailReportArray = array();          
            //Get the list of sources of a specific user            
            $sources = $this->getContainer()->get('app.svc.alerts')->getUserSourcesWithAlertsFlag($currentUser['USE_ID']);        
            foreach($sources as $currentSource) {    
                $currentSourceId = $currentSource['SOURCEID'];
                if (isset($sourceDailyReport[$currentSourceId])) {
                    self::log($output, 'info', 'DailyReportCommand.php\parseReports() - Adding source: ' . $currentSourceId . ' to the email report');                    
                    array_push($emailReportArray, $sourceDailyReport[$currentSourceId]);
                } else {
                    self::log($output, 'info', 'DailyReportCommand.php\parseReports() - Not adding source: ' . $currentSourceId . ' to the email report');                                        
                }
            }     
            if (count($emailReportArray) > 0) {
                self::sendReportEmail($output, $emailReportArray, $currentUser);                
            } else {
                self::log($output, 'info', 'DailyReportCommand.php\parseReports() - Report empty, skipping email...');                
            }
        }            
    }

    protected function getOverallReportScore(OutputInterface $output, $emailReportArray) {
        self::log($output, 'info', 'AlertsCommand.php\getOverallReportScore()');
        $scoreArray = array();
        foreach($emailReportArray as $report) {
            if (isset($report['schedule']) && isset($report['schedule']['overall']) && isset($report['schedule']['overall']['score']) && $report['schedule']['overall']['score'] != '-') {
                array_push($scoreArray, $report['schedule']['overall']['score']);
            }
        }
        if (count($scoreArray) > 0) {
            return array_sum($scoreArray) / count($scoreArray); 
        } else {
            return '-';
        }
    }


    protected function sendReportEmail(OutputInterface $output, $emailReportArray, $currentUser) {
        self::log($output, 'info', 'AlertsCommand.php\sendReportEmail()');

        self::log($output, 'info', 'AlertsCommand.php\sendReportEmail() - serialize: ' . serialize($emailReportArray));
        
        $sourceLocale = $this->getContainer()->get('app.svc.configuration')->getSourceConfigurationParameterValue($this->getContainer()->getParameter('dir_etc') . 'config-source' . $emailReportArray[0]['sourceId'] . '.cfg', 'cfgsourcelanguage');

        $templateSubjectFile = $this->getContainer()->getParameter('dir_locale') . $sourceLocale . '/emails/dailyReportSubject.txt';
        if (!file_exists($templateSubjectFile)) {$templateSubjectFile = $this->getContainer()->getParameter('dir_locale') . 'en_US.utf8/emails/dailyReportSubject.txt';}
        $templateContentFile = $this->getContainer()->getParameter('dir_locale') . $sourceLocale . '/emails/dailyReportContentTxt.twig';
        if (!file_exists($templateContentFile)) {$templateContentFile = $this->getContainer()->getParameter('dir_locale') . 'en_US.utf8/emails/dailyReportContentTxt.twig';}
        $templateContentHtmlFile = $this->getContainer()->getParameter('dir_locale') . $sourceLocale . '/emails/dailyReportContentHtml.twig';
        if (!file_exists($templateContentHtmlFile)) {$templateContentHtmlFile = $this->getContainer()->getParameter('dir_locale') . 'en_US.utf8/emails/dailyReportContentHtml.twig';}
        
        $emailSubject = file_get_contents($templateSubjectFile);
        $emailSubject = str_replace("#CURRENTHOSTNAME#", gethostname(), $emailSubject);        
        $emailSubject = str_replace("#REPORTSCORE#", self::getOverallReportScore($output, $emailReportArray), $emailSubject);        
        $emailSubject = str_replace("#REPORTDAY#", $emailReportArray[0]['reportDay'], $emailSubject);        

        $emailBodyHtml = $this->getContainer()->get('templating')->render($templateContentHtmlFile,array('emailReportArray' => $emailReportArray));     
        $emailBodyTxt = $this->getContainer()->get('templating')->render($templateContentFile,array('emailReportArray' => $emailReportArray));     
        
        $emailsParams = array(
            'EMAIL_FROM' => $this->getContainer()->getParameter('mailer_from')
            , 'EMAIL_TO' => $currentUser['EMAIL']
            , 'EMAIL_CC' => ''
            , 'SUBJECT' => $emailSubject
            , 'BODY' => $emailBodyTxt
            , 'BODYHTML' => $emailBodyHtml
            , 'ATTACHMENT_PATH' => ''
            , 'ATTACHMENT_NAME' => ''
            , 'ATTACHMENT_SOURCEID' => ''
        );
        
        $this->getContainer()->get('app.svc.emails')->prepareEmailForQueue($emailsParams);
    }

    protected function processSchedule(OutputInterface $output, $currentDayOfWeek, $currentDateYmd, $capturedJpgs, $capturedRaws, $scheduleArray, $sourceProcessRaw) {
        self::log($output, 'info', 'DailyReportCommand.php\processSchedule()');
        $totalPlannedCapturedInSchedule = 0;
        $jpgCaptureMissingAtScheduleCount = 0;
        $jpgCaptureInScheduleCount = 0;
        $rawCaptureMissingAtScheduleCount = 0;
        $rawCaptureInScheduleCount = 0;
        $overallSchedule = array();
        $overallScore = 0;
        for ($h=0;$h<24;$h++) {
            for ($m=0;$m<60;$m++) {
                if ($h < 10) {$fullHour = '0' . $h;} else {$fullHour = $h;}                
                if ($m < 10) {$fullMinute = '0' . $m;} else {$fullMinute = $m;}                
                $pictureDatehourMinute = $currentDateYmd . $fullHour . $fullMinute;
                if (isset($scheduleArray[$currentDayOfWeek][$h][$m])) { // Means capture was expected at this slot, and we are going to record something into the array
                    $overallSchedule[$currentDayOfWeek][$h][$m]['schedule'] = true;
                    if (isset($capturedJpgs[$pictureDatehourMinute])) {
                        $overallSchedule[$currentDayOfWeek][$h][$m]['jpg'] = $capturedJpgs[$pictureDatehourMinute]['filename'];
                        $jpgCaptureInScheduleCount++;
                    } else {
                        $jpgCaptureMissingAtScheduleCount++;
                    }
                    if (isset($capturedRaws[$pictureDatehourMinute])) {
                        $overallSchedule[$currentDayOfWeek][$h][$m]['raw'] = $capturedJpgs[$pictureDatehourMinute]['filename'];
                        $rawCaptureInScheduleCount++;
                    } else {
                        $rawCaptureMissingAtScheduleCount++;
                    }
                    if (!isset($capturedJpgs[$pictureDatehourMinute]) && !isset($capturedRaws[$pictureDatehourMinute])) {
                        $overallSchedule[$currentDayOfWeek][$h][$m]['success'] = false;
                    }
                    $totalPlannedCapturedInSchedule++;
                } elseif (isset($capturedJpgs[$pictureDatehourMinute]) || isset($capturedRaws[$pictureDatehourMinute])) { // Means a JPG picture was captured at this slot but we are outside the planned schedule
                    $overallSchedule[$currentDayOfWeek][$h][$m]['schedule'] = false; 
                    if (isset($capturedJpgs[$pictureDatehourMinute])) {
                        $overallSchedule[$currentDayOfWeek][$h][$m]['jpg'] = $capturedJpgs[$pictureDatehourMinute]['filename'];                        
                    }
                    if (isset($capturedRaws[$pictureDatehourMinute])) { // Means a JPG picture was captured at this slot but we are outside the planned schedule
                        $overallSchedule[$currentDayOfWeek][$h][$m]['raw'] = $capturedRaws[$pictureDatehourMinute]['filename'];
                    }                     
                }
            }            
        }
        if ($totalPlannedCapturedInSchedule > 0) {
            $jpgSuccessRate = round($jpgCaptureInScheduleCount * 100 / $totalPlannedCapturedInSchedule) . '%';
            $rawSuccessRate = round($rawCaptureInScheduleCount * 100 / $totalPlannedCapturedInSchedule) . '%';
            if ($sourceProcessRaw == 'no') {
                $overallScore = round($jpgCaptureInScheduleCount * 100 / $totalPlannedCapturedInSchedule);                
            } else {
                $overallScore = round(($jpgCaptureInScheduleCount + $rawCaptureInScheduleCount) * 100 / ($totalPlannedCapturedInSchedule*2));
            }                      
        } else {
            $jpgSuccessRate = '-';
            $rawSuccessRate = '-';
            $overallScore = '-';
        }
        if ($sourceProcessRaw == 'no') {
            $rawSuccessRate = '-';      
        }
        
        return array(
                'overall' => array(
                    'plannedSlots' => $totalPlannedCapturedInSchedule
                    , 'schedule' => $overallSchedule
                    , 'score' => $overallScore
                )
                , 'jpg' => array(
                    'missing' => array(
                        'count' => $jpgCaptureMissingAtScheduleCount
                    )
                    , 'successCount' => $jpgCaptureInScheduleCount
                    , 'successRate' => $jpgSuccessRate
                )
                , 'raw' => array(
                    'missing' => array(
                        'count' => $rawCaptureMissingAtScheduleCount
                    )
                    , 'successCount' => $rawCaptureInScheduleCount
                    , 'successRate' => $rawSuccessRate                
                ));
        
    }

    protected function compareCapturedReportWithCaptureSchedule(OutputInterface $output, $capturedJpgs, $capturedRaws, $scheduleArray, \DateTime $reportDay, $sourceProcessRaw) {
        self::log($output, 'info', 'DailyReportCommand.php\compareCapturedReportWithCaptureSchedule()');        
        $currentDayOfWeek = $reportDay->format('N');        
        $currentDateYmd = $reportDay->format('Ymd');        
        self::log($output, 'info', 'DailyReportCommand.php\compareCapturedReportWithCaptureSchedule() - Processing Day of Week: ' . $currentDayOfWeek);
                        
        $scheduleReport = self::processSchedule($output, $currentDayOfWeek, $currentDateYmd, $capturedJpgs, $capturedRaws, $scheduleArray, $sourceProcessRaw);
                 
        $jpgReport = self::getPictureCount($output, $scheduleArray, $currentDayOfWeek, $capturedJpgs);
        $rawReport = self::getPictureCount($output, $scheduleArray, $currentDayOfWeek, $capturedRaws);
        $report = array(
            'schedule' => $scheduleReport
            , 'jpg' => $jpgReport
            , 'raw' => $rawReport
            , 'total' => array(
                'count' => $jpgReport['count'] + $rawReport['count']
                , 'size' => $jpgReport['size'] + $rawReport['size']
            )
        );   
        return $report;
    }

    protected function getPictureCount(OutputInterface $output, $scheduleArray, $currentDayOfWeek, $capturedPictures) {
        $picturesOutsideScheduleCount = 0;
        $picturesCount = 0;
        $picturesSize = 0;
        foreach ($capturedPictures as $idx => $currentPicture) {
            $capturedHour = substr($idx, 8,2);
            $capturedMinute = substr($idx, 10,2);
            if (!isset($scheduleArray[$currentDayOfWeek][$capturedHour][$capturedMinute])) {
                $picturesOutsideScheduleCount++;
            }
            $picturesSize = $picturesSize + $currentPicture['size'];            
            $picturesCount++;
        }    
        return array(
            'outsideScheduleCount' => $picturesOutsideScheduleCount
            , 'count' => $picturesCount
            , 'size' => $picturesSize            
        );

    }
        
}

