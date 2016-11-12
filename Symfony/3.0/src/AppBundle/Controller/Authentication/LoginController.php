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

class LoginController extends Controller {

    public function indexAction(Request $request) {
        $logger = $this->get('logger');
        $logger->info('AppBundle\Controller\LoginController.php\indexAction() - Start');

        $receivedUsername = $request->request->get('USERNAME');
        $receivedPassword = $request->request->get('PASSWORD');
        $receivedRememberMe = $request->request->get('REMEMBERME');
        $logger->info('AppBundle\Controller\LoginController.php\indexAction() - USERNAME:' . $receivedUsername);
        $logger->info('AppBundle\Controller\LoginController.php\indexAction() - REMEMBERME:' . $receivedRememberMe);

        if ($receivedUsername != '' && $receivedPassword != '') {
            //Look for user entity based on username
            $user = $this->getDoctrine()
                ->getRepository('AppBundle:Users')
                ->findOneByUsername($receivedUsername);
            if ($user) {
                //We generate an encoded password based upon received password
                $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                $encodedPassword = $encoder->encodePassword($receivedPassword, $user->getSalt());
                $logger->info('AppBundle\Controller\LoginController.php\indexAction() - User Encoded Password: ' . $user->getPassword());
                $logger->info('AppBundle\Controller\LoginController.php\indexAction() - User Received encoded Password: ' . $encodedPassword);
                if ($encodedPassword == $user->getPassword()) {
                    // Create Auth Token
                    $results = array("success" => true, "message" => "Authentication successful", "authentication" => "SUCCESS", "USERNAME" => $receivedUsername);
                    $return = new JsonResponse($results);

                    // Here, "public" is the name of the firewall in your security.yml
                    $logger->info('AppBundle\Controller\LoginController.php\indexAction() - Creating security token');
                    $token = new UsernamePasswordToken($user, null, "secured_area", $user->getRoles());
                    $this->get("security.token_storage")->setToken($token);

                    // Fire the login event
                    // Logging the user in above the way we do it doesn't fire the login event automatically
                    $logger->info('AppBundle\Controller\LoginController.php\indexAction() - Authenticating user');
                    $event = new InteractiveLoginEvent($request, $token);
                    $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                    // Remember me
                    if ($receivedRememberMe == 'Y') {
                        // write cookie for persistent session storing
                        $providerKey = 'secured_area'; // defined in security.yml
                        $securityKey = $this->container->getParameter('secret'); // defined in security.yml
                        $logger->info('AppBundle\Controller\LoginController.php\indexAction() - Systems secret: ' . $securityKey);

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
                    }
                } else {
                    $results = array("success" => true, "message" => "Unable to log-in (username and/or password incorrect)", "authentication" => "FAILED");
                }
            } else {
                $results = array("success" => true, "message" => "Unable to log-in (username and/or password incorrect)", "authentication" => "FAILED");
            }
        } else {
            $results = array("success" => true, "message" => "Empty username or empty password provided", "authentication" => "FAILED");
        }
        if (!isset($return)) {
            $return = new JsonResponse($results);
        }
        return $return;
    }
}



