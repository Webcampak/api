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

class ChangepasswordController extends Controller {

    public function indexAction(Request $request) {
        if ($request->request->get('OLDPASSWORD') != "" && strlen($request->request->get('NEWPASSWORD')) >= 4) {
            $userEntity = $this->container->get('security.token_storage')->getToken()->getUser();
            if ($userEntity) {
                $encoder = $this->get('security.encoder_factory')->getEncoder($userEntity);
                $oldpasswordEncoded = $encoder->encodePassword($request->request->get('OLDPASSWORD'), $userEntity->getSalt());

                if ($oldpasswordEncoded == $userEntity->getPassword()) {
                    $newpasswordEncoded = $encoder->encodePassword($request->request->get('NEWPASSWORD'), $userEntity->getSalt());

                    $userEntity->setPassword($newpasswordEncoded);
                    $userEntity->setChangePwdFlag('N');

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($userEntity);
                    $em->flush();
                    return new JsonResponse(array("success" => true, "message" => "Password modified", "status" => "SUCCESS"));
                } else {
                    return new JsonResponse(array("success" => true, "message" => "Error, old password is incorrect. Please try again.", "status" => "FAILED"));
                }
            }
        }
        return new JsonResponse(array("success" => true, "message" => "Error, unable to find password. Please try again.", "status" => "FAILED"));
    }
 }
