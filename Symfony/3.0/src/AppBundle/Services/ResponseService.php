<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Symfony\Component\HttpFoundation\Response;

class ResponseService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
    }

    public function htmlDoesNotExist($page) {
        $this->logger->info('AppBundle\Services\ResponseService\logConfigurationChange() - Start');
        $response = new Response();
        $response->setContent('<html><body><h1>This page does not exists</h1><p>' . $page . '</p></body></html>');
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
   }

    public function htmlUnableToAccessContent($page) {
        $response = new Response();
        $response->setContent('<html><body><h1>Unable to access content</h1><p>Possible reasons:</p><ul><li>Resource does not exist</li><li>User not allowed to access resource</li><li>User not authenticated</li><p>' . $page . '</p></body></html>');
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
   }

}
