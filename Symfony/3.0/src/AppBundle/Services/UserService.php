<?php
namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Process\Process;

use AppBundle\Entities\Database\Users;

class UserService
{
    public function __construct(TokenStorage $tokenStorage, Doctrine $doctrine, Logger $logger, $kernelRootDir, $appCore, $appCli, $appApi, $appUi) {
        $this->tokenStorage      = $tokenStorage;
        $this->em                   = $doctrine->getManager();
        $this->logger               = $logger;
        $this->connection           = $doctrine->getConnection();
        $this->doctrine             = $doctrine;
        $this->kernelRootDir        = $kernelRootDir;
        $this->currentUserEntity    = $tokenStorage->getToken()->getUser();
        $this->appCore      = $appCore;
        $this->appCli       = $appCli;
        $this->appApi       = $appApi;
        $this->appUi        = $appUi;
    }

    public function getUserPermissions(Users $userEntity) {
        $this->logger->info('AppBundle\Services\UserService\getUserPermissions()');
        $sqlQuery = "SELECT PER.NAME NAME
                     FROM GROUPS_PERMISSIONS GROPER
                     LEFT JOIN PERMISSIONS PER ON PER.PER_ID = GROPER.PER_ID
                     WHERE GROPER.GRO_ID = :receivedGroId
                     ORDER BY PER.NAME";
        $userPermissionsDbResults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedGroId' => $userEntity->getGro()->getGroId()));
        $userPermissions = array();
        foreach($userPermissionsDbResults as $key=>$value) {
            $this->logger->info('AppBundle\Services\UserService\getUserPermissions() - User has permission: ' . $value['NAME']);
            array_push($userPermissions, $value['NAME']);
        }
        return $userPermissions;
    }

    public function getUserApplications(Users $userEntity) {
        $this->logger->info('AppBundle\Services\UserService\getUserApplications()');
        $sqlQuery = "SELECT APP.CODE NAME
                     FROM GROUPS_APPLICATIONS GROAPP
                     LEFT JOIN APPLICATIONS APP ON APP.APP_ID = GROAPP.APP_ID
                     WHERE GROAPP.GRO_ID = :receivedGroId
                     ORDER BY APP.CODE";
        $userApplicationsDbResults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedGroId' => $userEntity->getGro()->getGroId()));
        $userApplications = array();
        foreach($userApplicationsDbResults as $key=>$value) {
            $this->logger->info('AppBundle\Services\UserService\getUserApplications() - User has applications: ' . $value['NAME']);
            array_push($userApplications, $value['NAME']);
        }        
        return $userApplications;
    }

    public function testUserApplication(Users $userEntity, $applicationCode) {
        $this->logger->info('AppBundle\Services\UserService\testUserApplication()');
        $sqlQuery = "SELECT APP.CODE NAME
                     FROM GROUPS_APPLICATIONS GROAPP
                     LEFT JOIN APPLICATIONS APP ON APP.APP_ID = GROAPP.APP_ID
                     WHERE GROAPP.GRO_ID = :receivedGroId AND APP.CODE = :applicationCode
                     ORDER BY APP.CODE";
        $userApplicationsDbResults = $this->doctrine
            ->getManager()
            ->getConnection()
            ->fetchAll($sqlQuery, array('receivedGroId' => $userEntity->getGro()->getGroId(), 'applicationCode' => $applicationCode));
        if (count($userApplicationsDbResults) === 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getCurrentSourcesByUseId($useId) {
        $this->logger->info('AppBundle\Services\UserService\getUserSources()');
        $sqlQuery = "
            SELECT
                SOU.SOU_ID              SOU_ID
                , SOU.SOURCEID          SOURCEID
                , SOU.NAME              NAME
                , SOU.QUOTA             QUOTA
                , SOU.WEIGHT            WEIGHT
                , SOU.REMOTE_HOST       REMOTE_HOST
                , USESOU.USE_ID         USE_ID
                , USESOU.USESOU_ID      USESOU_ID
                , USESOU.ALERTS_FLAG    ALERTS_FLAG
            FROM
                USERS_SOURCES USESOU
            LEFT JOIN SOURCES SOU ON SOU.SOU_ID = USESOU.SOU_ID
            WHERE
                USESOU.USE_ID = :receivedUseId
            ORDER BY SOU.WEIGHT, SOU.NAME
        ";
        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedUseId' => $useId));
        return $dbresults;
    }

    public function getAvailableSourcesByUseId($useId) {
        $this->logger->info('AppBundle\Services\UserService\getAvailableSourcesByUseId()');
        $sqlQuery = "
            SELECT
                SOU.SOU_ID SOU_ID
                , SOU.SOURCEID SOURCEID
                , SOU.NAME NAME
                , :receivedUseId USE_ID
                , 'N' ALERTS_FLAG
            FROM
                SOURCES SOU
            WHERE
                SOU_ID NOT IN (SELECT SOU_ID FROM USERS_SOURCES WHERE USE_ID = :receivedUseId)
            ORDER BY SOU.WEIGHT, SOU.NAME
        ";
        $dbresults = $this->doctrine
                  ->getManager()
                  ->getConnection()
                  ->fetchAll($sqlQuery, array('receivedUseId' => $useId));
        return $dbresults;
    }

    public function hasCurrentUserAccessToSourceId($sourceId) {
        $this->logger->info('AppBundle\Services\UserService\hasCurrentUserAccessToSourceId()');

        return self::hasUserAccessToSourceId($this->currentUserEntity, $sourceId);
    }
    
    public function hasUserAccessToSourceId($userEntity, $sourceId) {
        $this->logger->info('AppBundle\Services\UserService\hasUserAccessToSourceId()');

        $isAllowed = false;
        if (is_a($userEntity, 'AppBundle\Entities\Database\Users') && $userEntity->getUsername() == 'root') {
            $this->logger->info('AppBundle\Services\UserService\hasCurrentUserAccessToSourceId() - User is root, granting access to source');
            $isAllowed = true;
        } else if (is_a($$userEntity, 'AppBundle\Entities\Database\Users')) {
            $userSources = self::getCurrentSourcesByUseId($userEntity->getUseId());
            foreach($userSources as $currentUserSource) {
                if ($currentUserSource['SOURCEID'] == $sourceId) {
                    $this->logger->info('AppBundle\Services\UserService\hasCurrentUserAccessToSourceId() - User is allowed access to the source');
                    $isAllowed = true;
                }
            }
        }
        return $isAllowed;
    }    

    public function isMethodAllowed($callAction, $callMethod, $methodConfig) {
        //In this function we test if a user is allowed to access any applications in the list
        $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - Start');
        $allowedActionApplications = $methodConfig[$callAction]['applications'];
        $allowedMethodApplications = $methodConfig[$callAction]['methods'][$callMethod]['applications'];
        $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - Action: ' . $callAction . ' - Allowed Applications: ' . serialize($allowedActionApplications));
        $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - Method: ' . $callMethod . ' - Allowed Applications: ' . serialize($allowedMethodApplications));

        $isAllowed = false;
        //By default root is allowed to access all actions and methods
        if (is_a($this->currentUserEntity, 'AppBundle\Entities\Database\Users') && $this->currentUserEntity->getUsername() == 'root') {
            $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - User is root, granting access to method');
            $isAllowed = true;
        } else if (in_array('all', $allowedActionApplications) && in_array('all', $allowedMethodApplications)) {
            $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - Access to this application is permitted to anyone authenticated or not');
            $isAllowed = true;
        } else if (is_a($this->currentUserEntity, 'AppBundle\Entities\Database\Users')) {
            $userApplications = self::getUserApplications($this->currentUserEntity);
            $arrayActionApplicationIntersec = array_intersect($allowedActionApplications, $userApplications);            
            $arrayMethodApplicationIntersec = array_intersect($allowedMethodApplications, $userApplications);            
            if (count($arrayActionApplicationIntersec) > 0 && in_array('all', $allowedMethodApplications)) {
                $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - User is allowed to access application: ' . serialize($allowedMethodApplications) . ' (Permission granted to Action, method allow all applications)');
                $isAllowed = true;
            } else if (in_array('all', $allowedActionApplications) && count($arrayMethodApplicationIntersec) > 0) {
                $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - User is allowed to access application: ' . serialize($allowedMethodApplications) . ' (Permission granted to method, action allow all applications)');
                $isAllowed = true;
            } else if (count($arrayActionApplicationIntersec) > 0 && count($arrayMethodApplicationIntersec) > 0) {
                $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - User is allowed to access application: ' . serialize($allowedMethodApplications) . ' (Permission granted to method and action)');
                $isAllowed = true;
            }
        }
        $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - Return: ' . var_export($isAllowed, true));
        return $isAllowed;
    }

    public function isApplicationAllowed($application) {
        //In this function we test if a user is allowed to access a particular application
        $this->logger->info('AppBundle\Services\UserService\isApplicationAllowed() - Start');
        $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - Tested Applications: ' . $application);

        //By default root is allowed to access all actions and methods
        if (is_a($this->currentUserEntity, 'AppBundle\Entities\Database\Users') && $this->currentUserEntity->getUsername() == 'root') {
            $this->logger->info('AppBundle\Services\UserService\isMethodAllowed() - User is root, granting access to method');
            return true;
        } else if (is_a($this->currentUserEntity, 'AppBundle\Entities\Database\Users')) {
            return self::testUserApplication($this->currentUserEntity, $application);
        }
    }

    public function getSettings(Users $userEntity) {
        $this->logger->info('AppBundle\Services\UserService\getSettings() - Start');
        $dbresults = array();

        if ($userEntity->getUsername() != '') {
            array_push($dbresults, array('CODE' => 'CURRENTUSERNAME', 'VALUE' => $userEntity->getUsername()));
        }

        if ($userEntity->getUsername() != '') {
            array_push($dbresults, array('CODE' => 'CURRENTUSEID', 'VALUE' => $userEntity->getUseId()));
        }

        if ($userEntity->getChangePwdFlag() == 'Y') {
            array_push($dbresults, array('CODE' => 'CHANGEPASSWORD', 'VALUE' => 'Y'));
        }

        if ($userEntity->getCus() !== null && $userEntity->getCus()->getStyleBgColor() != '') {
            array_push($dbresults, array('CODE' => 'STYLE_BG_COLOR', 'VALUE' => $userEntity->getCus()->getStyleBgColor()));
        }

        if ($userEntity->getCus() !== null && $userEntity->getCus()->getStyleBgLogo() != '') {
            array_push($dbresults, array('CODE' => 'STYLE_BG_LOGO', 'VALUE' => $userEntity->getCus()->getStyleBgLogo()));
        }

        $this->logger->info('AppBundle\Services\UserService\getSettings() - Get Software Version');
        $command = "git -C " . $this->appCore . " describe --tags";
        $getCoreVersion = new Process($command);
        $getCoreVersion->run();
        $coreVersion = preg_replace( "/\r|\n|\t/", "", $getCoreVersion->getOutput());
        $this->logger->info('AppBundle\Services\UserService\getSettings() - Get Software Version - Core: ' . $coreVersion);
        array_push($dbresults, array('CODE' => 'VERSION_CORE', 'VALUE' =>  $coreVersion));

        $command = "git -C " . $this->appUi . " describe --tags";
        $getUiVersion = new Process($command);
        $getUiVersion->run();
        $uiVersion = preg_replace( "/\r|\n|\t/", "", $getUiVersion->getOutput());
        $this->logger->info('AppBundle\Services\UserService\getSettings() - Get Software Version - UI: ' . $uiVersion);
        array_push($dbresults, array('CODE' => 'VERSION_UI', 'VALUE' =>  $uiVersion));

        $command = "git -C " . $this->appApi . " describe --tags";
        $getApiVersion = new Process($command);
        $getApiVersion->run();
        $apiVersion = preg_replace( "/\r|\n|\t/", "", $getApiVersion->getOutput());
        $this->logger->info('AppBundle\Services\UserService\getSettings() - Get Software Version - API: ' . $apiVersion);
        array_push($dbresults, array('CODE' => 'VERSION_API', 'VALUE' =>  $apiVersion));

        $command = "git -C " . $this->appCli . " describe --tags";
        $getCliVersion = new Process($command);
        $getCliVersion->run();
        $cliVersion = preg_replace( "/\r|\n|\t/", "", $getCliVersion->getOutput());
        $this->logger->info('AppBundle\Services\UserService\getSettings() - Get Software Version - CLI: ' . $cliVersion);
        array_push($dbresults, array('CODE' => 'VERSION_CLI', 'VALUE' =>  $cliVersion));

        //CURRENTBUILD is the number displayed in the toolbar, VERSION_* are additional version of the various components
        preg_match("/v(\d+)\.(\d+)\.(\d+)/", $uiVersion, $mainVersion);
        if ($mainVersion !== $uiVersion) {
            array_push($dbresults, array('CODE' => 'CURRENTBUILD', 'VALUE' => 'dev (' . $mainVersion[0] . ')'));
        } else {
            array_push($dbresults, array('CODE' => 'CURRENTBUILD', 'VALUE' => $mainVersion[0]));
        }

        $receivedSenchaApp = 'WPAKD';
        if (isset($inputParams['SENCHA_APP'])) {
              $receivedSenchaApp = $inputParams['SENCHA_APP'];
        }

        if ($receivedSenchaApp == 'WPAKD') {
            // Get current permissions for user
            if ($userEntity->getGro()) {
                $sqlQuery = "
                    SELECT
                        PER.NAME NAME
                    FROM
                        GROUPS_PERMISSIONS GROPER
                    JOIN PERMISSIONS PER ON GROPER.PER_ID = PER.PER_ID
                    WHERE
                        GROPER.GRO_ID = :groId
                    ORDER BY NAME
                    ";
                $userPermissions = $this->doctrine
                          ->getManager()
                          ->getConnection()
                          ->fetchAll($sqlQuery, array('groId' => $userEntity->getGro()->getGroId()));
                foreach($userPermissions as $currentPermission) {
                    if ($currentPermission['NAME'] != '') {
                        array_push($dbresults, array('CODE' => 'PERM-' . $currentPermission['NAME'], 'VALUE' => 'Y'));
                    }
                }
            }
        }
        return $dbresults;
    }
    
}
