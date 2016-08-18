<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class AlertsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger) {
        $this->tokenStorage = $tokenStorage;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
    }

    //Returns the list of users and sources with the alert flag enabled    
    public function getUsersSourcesWithAlertsFlag() {
        $this->logger->info('AppBundle\Services\AlertsService\getUsersWithAlertsFlag() - Start');

        $sqlQuery = "
            SELECT
                USE.USE_ID              USE_ID
                , USE.EMAIL             EMAIL
                , USESOU.ALERTS_FLAG    ALERTS_FLAG
                , SOU.SOURCEID          SOURCEID
                , SOU.NAME             SOURCENAME                
            FROM
                USERS USE
            LEFT JOIN USERS_SOURCES USESOU ON USE.USE_ID = USESOU.USE_ID
            LEFT JOIN SOURCES SOU ON USESOU.SOU_ID = SOU.SOU_ID
            WHERE USESOU.ALERTS_FLAG = 'Y'
            ORDER BY USE.USERNAME
        ";
        return $this->doctrine
                    ->getManager()
                    ->getConnection()
                    ->fetchAll($sqlQuery);                
    }
    
    public function getSingleUsersSourcesWithAlertsFlag() {
        $this->logger->info('AppBundle\Services\AlertsService\getUsersWithAlertsFlag() - Start');

        $sqlQuery = "
            SELECT
                USE.USE_ID              USE_ID
                , USE.EMAIL             EMAIL
                , USESOU.ALERTS_FLAG    ALERTS_FLAG              
            FROM
                USERS USE
            LEFT JOIN USERS_SOURCES USESOU ON USE.USE_ID = USESOU.USE_ID
            WHERE USESOU.ALERTS_FLAG = 'Y' AND USE.EMAIL != ''
            GROUP BY USE.EMAIL
            ORDER BY USE.USERNAME
        ";
        return $this->doctrine
                    ->getManager()
                    ->getConnection()
                    ->fetchAll($sqlQuery);                
    }

    public function getUserSourcesWithAlertsFlag($useId) {
        $this->logger->info('AppBundle\Services\AlertsService\getUsersWithAlertsFlag() - Start');

        $sqlQuery = "
            SELECT
                SOU.SOURCEID          SOURCEID
                , SOU.NAME            SOURCENAME               
            FROM
                USERS_SOURCES USESOU
            LEFT JOIN SOURCES SOU ON USESOU.SOU_ID = SOU.SOU_ID
            WHERE USESOU.USE_ID = :useId AND USESOU.ALERTS_FLAG = 'Y'
        ";
        return $this->doctrine
                    ->getManager()
                    ->getConnection()
                    ->fetchAll($sqlQuery, array('useId' => $useId));                
    }       
  
}
