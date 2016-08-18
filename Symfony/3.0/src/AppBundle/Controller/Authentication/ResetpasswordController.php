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

class ResetpasswordController extends Controller {

    public function indexAction(Request $request) {
        $logger = $this->get('logger');
        $logger->info('ResetpasswordController - indexAction()');

        $username = $request->request->get('username', 'ERR-DOESNOTEXIST');
        $email = $request->request->get('email', 'ERR-DOESNOTEXIST');
       
        if ($username != 'ERR-DOESNOTEXIST' && $email != 'ERR-DOESNOTEXIST') {
            $logger->info('ResetpasswordController - indexAction(): (POST) Username:' . $username . ' - Email: ' . $email);
            $user = $this->getDoctrine()
                      ->getRepository('AppBundle:Users')
                      ->findOneByUsername($username);
            if (!$user) {
                $logger->info('ResetpasswordController - indexAction(): ERROR: Could not find a user with the following username: ' . $username);
                $results = array("success" => true, "message" => "User doest not exist", "authentication" => "FAILED");
                return new JsonResponse($results);
            } else {
                $logger->info('ResetpasswordController - indexAction(): User found with username: ' . $username);
                $logger->info('ResetpasswordController - indexAction(): Email is: ' . $user->getEmail());
                if ($user->getEmail() != $email) {
                    $logger->info('ResetpasswordController - indexAction(): ERROR: User email is incorrect' . $username . ' correct email is: ' . $user->getEmail());
                    $results = array("success" => true, "message" => "User email is incorrect", "authentication" => "FAILED");
                    return new JsonResponse($results);
                } else {
                    //Generate random password
                    $logger->info('ResetpasswordController - indexAction(): User found in database');

                    //We generate clear-text new password
                    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $newPassword = '';
                    for ($i = 0; $i < 10; $i++) {
                        $newPassword .= $characters[rand(0, strlen($characters) - 1)];
                    }

                    //We create a writeable user object
                    $userEntity =  $this->getDoctrine()->getRepository('AppBundle:Users')->find($user->getUseId());
                    if (strlen($user->getSalt()) < 10) {
                        $logger->info('ResetpasswordController - indexAction(): Initial salt: ' . $user->getSalt());
                        $userEntity->setSalt(sha1($user->getEmail() . microtime()));
                        $logger->info('ResetpasswordController - indexAction(): New salt: ' . $userEntity->getSalt());
                    }

                    //We generate a new encoded password
                    $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                    $newPasswordEncoded = $encoder->encodePassword($newPassword, $userEntity->getSalt());

                    $logger->info('ResetpasswordController - indexAction(): New clear-text password has been generated: ' . $newPassword);
                    $logger->info('ResetpasswordController - indexAction(): Old encoded password was: ' . $user->getPassword());
                    $logger->info('ResetpasswordController - indexAction(): New encoded password is: ' . $newPasswordEncoded);

                    $userEntity->setPassword($newPasswordEncoded);
                    $userEntity->setChangePwdFlag('Y');
                    $this->getDoctrine()->getManager()->persist($userEntity);
                    $this->getDoctrine()->getManager()->flush();
                    $logger->info('ResetpasswordController - indexAction(): New password updated in database');

                    $logger->info('ResetpasswordController - indexAction(): Password reset request coming from: ' . $request->getClientIp());

                    //We send an email with all details
                    $message = \Swift_Message::newInstance()
                                ->setSubject('New password to access your Webcampak')
                                ->setFrom('support@webcampak.com')
                                ->setTo($email)
                                ->setBody(
                                $this->renderView(
                                      'AppBundle:Emails:resetpassword.html.twig', array('username' => $username
                                      , 'newPassword' => $newPassword
                                      , 'currentIP' => $request->getClientIp()
                                  )
                                )
                                , 'text/html')
                    ;
                    $this->get('mailer')->send($message);
                    $logger->info('ResetpasswordController - indexAction(): Email with credentials sent');
                }
            }
        }
        $results = array("success" => true, "message" => "Password reset successful", "authentication" => "SUCCESS");
        return new JsonResponse($results);
    }

}
