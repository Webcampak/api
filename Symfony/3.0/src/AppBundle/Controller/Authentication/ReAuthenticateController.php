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

namespace AppBundle\Controller\Authentication;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;

class ReAuthenticateController extends Controller {

    public function indexAction(Request $request) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - Start');

        $receivedAuthToken = $request->request->get('AUTH_TOKEN');
        $receivedUsername = $request->request->get('USERNAME');
        $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - USERNAME:' . $receivedUsername);
        $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - AUTH_TOKEN:' . $receivedAuthToken);

        if ($receivedUsername != '' && $receivedAuthToken != '') {
            // Look for Use_id based on username and auth token
            $identifiedUseId = $this->getDoctrine()
                      ->getManager()
                      ->getConnection()
                      ->fetchColumn("SELECT USE_ID FROM USERS WHERE USERNAME = :username AND AUTH_TOKEN = :authToken AND AUTH_TOKEN_EXPIRY > date('now')"
                                 , array('username' => $receivedUsername, 'authToken' => $receivedAuthToken), 0);
            $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - Identified USE_ID: ' . $identifiedUseId);
            if (intval($identifiedUseId) > 0) {
                //Look for user entity based on PK (use_id)
                $user = $this->getDoctrine()
                    ->getRepository('AppBundle:Users')
                    ->find($identifiedUseId);
                if ($user) {
                    // Create Auth Token
                    $authToken = sha1($receivedUsername . time() . rand(1,999999999));
                    $results = array("success" => true, "message" => "Authentication successful", "authentication" => "SUCCESS", "AUTH_TOKEN" => $authToken, "USERNAME" => $receivedUsername);
                    $return = new JsonResponse($results);

                    // Here, "public" is the name of the firewall in your security.yml
                    $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - Creating security token');
                    $token = new UsernamePasswordToken($user, null, "secured_area", $user->getRoles());
                    $this->get("security.token_storage")->setToken($token);

                    // Fire the login event
                    // Logging the user in above the way we do it doesn't fire the login event automatically
                    $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - Authenticating user');
                    $event = new InteractiveLoginEvent($this->get("request"), $token);
                    $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                    // We automatically re-set cookies and write cookie for persistent session storing
                    $providerKey = 'secured_area'; // defined in security.yml
                    $securityKey = $this->container->getParameter('secret'); // defined in security.yml
                    $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - Systems secret: ' . $securityKey);

                    $userProvider = new EntityUserProvider($this->getDoctrine(), 'AppBundle:Users', $receivedUsername);

                    $rememberMeService = new TokenBasedRememberMeServices(array($userProvider), $securityKey, $providerKey, array(
                                         'path' => '/',
                                         'name' => 'MyRememberMeCookie',
                                         'domain' => null,
                                         'secure' => false,
                                         'httponly' => true,
                                         'lifetime' => 1209600, // 14 days
                                         'always_remember_me' => true,
                                         'remember_me_parameter' => '_remember_me')
                                    );
                    $rememberMeService->loginSuccess($request, $return, $token);


                    $logger->info('AppBundle\Controller\ReAuthenticateController.php\indexAction() - Saving Auth Token');
                    $userEntity = $this->getDoctrine()->getRepository('AppBundle:Users')->find($user->getUseId());
                    $authTokenExpiry = new \DateTime("now");
                    $authTokenExpiry->add(new \DateInterval('PT2H'));
                    $userEntity->setAuthTokenExpiry($authTokenExpiry);
                    $userEntity->setAuthToken($authToken);

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($userEntity);
                    $em->flush();
                } else {
                    $results = array("success" => true, "message" => "This user does not exist", "authentication" => "FAILED");
                }
            } else {
                $results = array("success" => true, "message" => "No Credentials provided", "authentication" => "FAILED");
            }
        } else {
            $results = array("success" => true, "message" => "No Credentials provided", "authentication" => "FAILED");
        }
        if (!isset($return)) {
            $return = new JsonResponse($results);
        }
        return $return;
    }
}



