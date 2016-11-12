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

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; // for Symfony 2.1.0+
use Symfony\Component\HttpFoundation\RequestStack;
use Monolog\Logger;

use AppBundle\Entities\Database\LoginHistory;

/**
 * Custom login listener.
 */
class LoginListener
{
   /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage */
   private $tokenStorage;

   /** @var \Doctrine\ORM\EntityManager */
   private $em;

   private $logger;

    private $requestStack;


    /**
     * Constructor
     *
     * @param TokenStorage $tokenStorage
     * @param Doctrine        $doctrine
     */
    public function __construct(TokenStorage $tokenStorage, AuthorizationChecker $authorizationChecker, Doctrine $doctrine, Logger $logger, RequestStack $requestStack) {
        $this->tokenStorage             = $tokenStorage;
        $this->authorizationChecker     = $authorizationChecker;
        $this->em              = $doctrine->getManager();
        $this->logger          = $logger;
        $this->connection      = $doctrine->getConnection();
        $this->doctrine        = $doctrine;
        $this->requestStack    = $requestStack;
    }

    /**
     * Do the magic.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin() {
        $this->logger->info('AppBundle\EventListerner\LoginListerner.php\onSecurityInteractiveLogin() - User authenticated successfully');
        $userEntity = $this->tokenStorage->getToken()->getUser();
        $request = $this->requestStack->getCurrentRequest();

        $this->logger->info('AppBundle\EventListerner\LoginListerner.php\onSecurityInteractiveLogin() - Username: ' . $userEntity->getUsername());
        $this->logger->info('AppBundle\EventListerner\LoginListerner.php\onSecurityInteractiveLogin() - Use_id: ' . $userEntity->getUseId());
        $this->logger->info('AppBundle\EventListerner\LoginListerner.php\onSecurityInteractiveLogin() - Change_Password_Flag: ' . $userEntity->getChangePwdFlag());
        $this->logger->info('AppBundle\EventListerner\LoginListerner.php\onSecurityInteractiveLogin() - Firstname: ' . $userEntity->getFirstName());
        $this->logger->info('AppBundle\EventListerner\LoginListerner.php\onSecurityInteractiveLogin() - IP: ' . $request->getClientIp());
        $this->logger->info('AppBundle\EventListerner\LoginListerner.php\onSecurityInteractiveLogin() - USER_AGENT: ' .  $request->headers->get('User-Agent'));

        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            // user has just logged in
        }

        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            // user has logged in using remember_me cookie
        }

        $loginHistoryEntity = new LoginHistory();
        $loginHistoryEntity->setIpAddress($request->getClientIp());
        $loginHistoryEntity->setUsername($userEntity->getUsername());
        $userAgent = $request->headers->get('User-Agent');
        if (isset($userAgent)) {
           $loginHistoryEntity->setUserAgent(substr($userAgent, 0, 49));
        }
        $loginHistoryEntity->setDateAccess(new \DateTime("now"));
        $this->em->persist($loginHistoryEntity);

        // Prepare login history container to get users connected/disconnected status
        if (isset($userAgent) && $userAgent != '') {
            $searchConnectedStatusEntity = $this->doctrine
                ->getRepository('AppBundle:LoginHistory')
                ->findOneBy(array('username' => $userEntity->getUsername(), 'userAgent' => 'CONNECTEDPING'));
            if (!$searchConnectedStatusEntity) {
                $searchConnectedStatusEntity = new LoginHistory();
                $searchConnectedStatusEntity->setUsername($userEntity->getUsername());
                $searchConnectedStatusEntity->setUserAgent('CONNECTEDPING');
            }
            $searchConnectedStatusEntity->setIpAddress($request->getClientIp());
            $searchConnectedStatusEntity->setDateAccess(new \DateTime("now"));
            $this->em->persist($searchConnectedStatusEntity);
        }
        $this->em->flush();
    }

}

?>
