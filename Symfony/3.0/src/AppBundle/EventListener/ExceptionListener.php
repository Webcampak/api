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
namespace AppBundle\EventListener;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener {
    //public function __construct(LoggerInterface $logger) {
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event) {
    //public function onKernelException(GetResponseEvent $event) {
        $this->logger->warn('AppBundle\EventListener\ExceptionListerner.php\onKernelException() - Start');
        
        // You get the exception object from the received event
        $exception = $event->getException();
        $this->logger->warn('AppBundle\EventListener\ExceptionListerner.php\onKernelException() - Exception Message: ' . $exception->getMessage());
        $this->logger->warn('AppBundle\EventListener\ExceptionListerner.php\onKernelException() - Exception Code: ' . $exception->getCode());
        $this->logger->warn('AppBundle\EventListener\ExceptionListerner.php\onKernelException() - Exception Trace: ' . $exception->getTraceAsString());

        $results = array("success" => false, "message" => $exception->getMessage(), "fullmessage" => $exception->getMessage(), "code" => $exception->getCode());
        $response = new JsonResponse($results);
        $response->headers->set( 'X-Status-Code', 200 );   
        
        $event->setResponse($response);
   }

}
?>
