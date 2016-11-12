<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ScheduleService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
    }

    //Takes a date and find on a schedule array the next time a picture is supposed to be captured
    public function getNextCaptureSlot($scheduleArray, \DateTime $currentDate, $debug = false) {
        $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Start');
        $currentDate = clone $currentDate;
        $currentDate->setTime ( $currentDate->format("H"), $currentDate->format("i"), 0); // Remove the 0 from seconds (useless)        
        if ($debug === true) {$this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Array: ' . serialize($scheduleArray));}      
        $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Current Date: ' . $currentDate->format('Y-m-d H:i:s'));
        $currentDay = intval($currentDate->format('N'));
        $currentHour = intval($currentDate->format('H'));
        $currentMinute = intval($currentDate->format('i'));
        
        // We start by going forward from current date, to the end of the week
        $pastCurrentDate = false;
        for($i=$currentDay; $i<8; $i++) {
            for($j=0; $j<24; $j++) {
                for($k=0; $k<60; $k++) {
                    //First we need to identify the current date / time
                    if ($i >= $currentDay && $j >= $currentHour && $k >= $currentMinute) {$pastCurrentDate = true;}
                    if ($pastCurrentDate === true) {
                        if ($debug === true) {
                            $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Scanning array at Day: ' . $i . ' Hour: ' . $j . ' Minute: ' . $k);
                            $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Time Currently at Day: ' . intval($currentDate->format('N')) . ' Hour: ' . intval($currentDate->format('H')) . ' Minute: ' . intval($currentDate->format('i')));                            
                        }
                        //We skip current minute
                        if (isset($scheduleArray[$i]) && isset($scheduleArray[$i][$j]) && isset($scheduleArray[$i][$j][$k])) {
                            if ($k == $currentMinute && $j == $currentHour && $i == $currentDay) {
                                $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Skipping Day: ' .$i . ' Hour: ' . $j . ' Minute: ' . $k . ' since it\'s the current capture time');                            
                            } else {
                                $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Found at Day: ' .$i . ' Hour: ' . $j . ' Minute: ' . $k);
                                return $currentDate;
                            }
                        }
                        $currentDate->add(new \DateInterval('PT60S'));
                    }
                } 
            }
        }
        //At this point, going forward did not give any results, so we'll restart scanning the array from the beginning
        for($i=1; $i<$currentDay+1; $i++) {
            for($j=0; $j<24; $j++) {
                for($k=0; $k<60; $k++) {
                    if ($i >= $currentDay && $j >= $currentHour && $k >= $currentMinute) {return false;} // If we get to this point, it means there is not upcoming planned captured, which should not be possible anyway.
                    if ($debug === true) {
                        $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - L: Scanning array at Day: ' . $i . ' Hour: ' . $j . ' Minute: ' . $k);
                        $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - L: Time Currently at Day: ' . intval($currentDate->format('N')) . ' Hour: ' . intval($currentDate->format('H')) . ' Minute: ' . intval($currentDate->format('i')));                            
                    }
                    //We skip current minute
                    if (isset($scheduleArray[$i]) && isset($scheduleArray[$i][$j]) && isset($scheduleArray[$i][$j][$k])) {
                        $this->logger->info('AppBundle\Services\ScheduleService\getNextCaptureSlot() - Found at Day: ' .$i . ' Hour: ' . $j . ' Minute: ' . $k);
                        return $currentDate;
                    }
                    $currentDate->add(new \DateInterval('PT60S'));
                }            
            }
        }
        //If nothing is found (which should not be possible, return false
        return false;        
    }

    //Takes a date and find on a schedule array the last time a picture is supposed to be captured  (inverse from getNextCaptureSlot)
    public function getPreviousCaptureSlot($scheduleArray, \DateTime $currentDate, $debug = false) {
        $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Start');
        $currentDate = clone $currentDate;
        $currentDate->setTime ( $currentDate->format("H"), $currentDate->format("i"), 0); // Remove the 0 from seconds (useless)        
        if ($debug === true) {$this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Array: ' . serialize($scheduleArray));}      
        $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Current Date: ' . $currentDate->format('Y-m-d H:i:s'));
        $currentDay = intval($currentDate->format('N'));
        $currentHour = intval($currentDate->format('H'));
        $currentMinute = intval($currentDate->format('i'));
        
        // We start by going backward from current date, to the beginning of the week
        $beforeCurrentDate = false;
        for($i=7; $i>0; $i--) {
            for($j=23; $j>-1; $j--) {
                for($k=59; $k>-1; $k--) {        
                    //First we need to identify the current date / time
                    if ($i <= $currentDay && $j <= $currentHour && $k <= $currentMinute) {$beforeCurrentDate = true;}
                    if ($beforeCurrentDate === true) {
                        if ($debug === true) {
                            $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Scanning array at Day: ' . $i . ' Hour: ' . $j . ' Minute: ' . $k);
                            $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Time Currently at Day: ' . intval($currentDate->format('N')) . ' Hour: ' . intval($currentDate->format('H')) . ' Minute: ' . intval($currentDate->format('i')));                            
                        }
                        //We skip current minute
                        if (isset($scheduleArray[$i]) && isset($scheduleArray[$i][$j]) && isset($scheduleArray[$i][$j][$k])) {
                            if ($k == $currentMinute && $j == $currentHour && $i == $currentDay) {
                                $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Skipping Day: ' .$i . ' Hour: ' . $j . ' Minute: ' . $k . ' since it\'s the current capture time');                            
                            } else {
                                $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Found at Day: ' .$i . ' Hour: ' . $j . ' Minute: ' . $k);
                                return $currentDate;
                            }
                        }
                        $currentDate->sub(new \DateInterval('PT60S'));
                    }
                } 
            }
        }
        //At this point, going forward did not give any results, so we'll restart scanning the array from the beginning
        for($i=7; $i>0; $i--) {
            for($j=23; $j>-1; $j--) {
                for($k=59; $k>-1; $k--) {  
                    if ($i <= $currentDay && $j <= $currentHour && $k <= $currentMinute) {return false;} // If we get to this point, it means there is not upcoming planned captured, which should not be possible anyway.
                    if ($debug === true) {
                        $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Scanning array at Day: ' . $i . ' Hour: ' . $j . ' Minute: ' . $k);
                        $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Time Currently at Day: ' . intval($currentDate->format('N')) . ' Hour: ' . intval($currentDate->format('H')) . ' Minute: ' . intval($currentDate->format('i')));                            
                    }
                    //We skip current minute
                    if (isset($scheduleArray[$i]) && isset($scheduleArray[$i][$j]) && isset($scheduleArray[$i][$j][$k])) {
                        $this->logger->info('AppBundle\Services\ScheduleService\getPreviousCaptureSlot() - Found at Day: ' .$i . ' Hour: ' . $j . ' Minute: ' . $k);
                        return $currentDate;
                    }
                    $currentDate->sub(new \DateInterval('PT60S'));
                }            
            }
        }
        //If nothing is found (which should not be possible, return false
        return false;        
    }    
    
}
