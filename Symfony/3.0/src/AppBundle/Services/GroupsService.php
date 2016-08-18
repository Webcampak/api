<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class GroupsService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger) {
        $this->tokenStorage      = $tokenStorage;
        $this->em                   = $doctrine->getManager();
        $this->logger               = $logger;
        $this->connection           = $doctrine->getConnection();
        $this->doctrine             = $doctrine;
    }


    public function getCurrentApplicationsByGroId($groId) {
        $this->logger->info('AppBundle\Services\GroupsService\getCurrentApplicationsByGroId()');
        $sqlQuery = "
            SELECT
                APP.APP_ID APP_ID
                , APP.NAME NAME
                , APP.NOTES NOTES
                , GROAPP.GRO_ID GRO_ID
                , GROAPP.GROAPP_ID GROAPP_ID
            FROM
                GROUPS_APPLICATIONS GROAPP
            LEFT JOIN APPLICATIONS APP ON APP.APP_ID = GROAPP.APP_ID
            WHERE
                GROAPP.GRO_ID = :receivedGroId
            ORDER BY APP.NAME
        ";

        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedGroId' => $groId));

        return $dbresults;
    }

    public function getAvailableApplicationsByGroId($groId) {
        $this->logger->info('AppBundle\Services\GroupsService\getAvailableApplicationsByGroId()');
        $sqlQuery = "
            SELECT
                APP.APP_ID APP_ID
                , APP.NAME NAME
                , APP.NOTES NOTES
                , :receivedGroId GRO_ID
            FROM
                APPLICATIONS APP
            WHERE
                APP_ID NOT IN (SELECT APP_ID FROM GROUPS_APPLICATIONS WHERE GRO_ID = :receivedGroId)
            ORDER BY APP.NAME
        ";
        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedGroId' => $groId));
        return $dbresults;
    }

    public function getCurrentPermissionsByGroId($groId) {
        $this->logger->info('AppBundle\Services\GroupsService\getCurrentPermissionsByGroId()');
        $sqlQuery = "
            SELECT
                PER.PER_ID PER_ID
                , PER.NAME NAME
                , PER.NOTES NOTES
                , GROPER.GRO_ID GRO_ID
                , GROPER.GROPER_ID GROPER_ID
            FROM
                GROUPS_PERMISSIONS GROPER
            LEFT JOIN PERMISSIONS PER ON PER.PER_ID = GROPER.PER_ID
            WHERE
                GROPER.GRO_ID = :receivedGroId
            ORDER BY PER.NAME
        ";

        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedGroId' => $groId));

        return $dbresults;
    }

    public function getAvailablePermissionsByGroId($groId) {
        $this->logger->info('AppBundle\Services\GroupsService\getAvailablePermissionsByGroId()');
        $sqlQuery = "
            SELECT
                PER.PER_ID PER_ID
                , PER.NAME NAME
                , PER.NOTES NOTES
                , :receivedGroId GRO_ID
            FROM
                PERMISSIONS PER
            WHERE
                PER_ID NOT IN (SELECT PER_ID FROM GROUPS_PERMISSIONS WHERE GRO_ID = :receivedGroId)
            ORDER BY PER.NAME
        ";
        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedGroId' => $groId));
        return $dbresults;
    }

}
