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

namespace AppBundle\Controller\Misc;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Filesystem\Filesystem;

class DownloadController extends Controller {

    /**
     * @Route("/dl/{page}", name="_dl", requirements={"page"=".+"})
     */
    public function indexAction($page, Request $request) {
        $logger = $this->get('logger');

        $page = str_replace("..", "", $page); // We strip any .. to avoid user trying to move to parent directory
        $completePath = $this->container->getParameter('dir_sources') . $page;

        $explodedPath = explode("/", $page);
        $logger->info('AppBundle\Controller\DownloadController.php\indexAction() - Complete Path: ' . $completePath . ' Source: ' . $explodedPath[0]);
        $sourceId = str_replace("source", "", $explodedPath[0]);
        if ($this->get('app.svc.sources')->isUserAllowed($sourceId)) {
            $fs = new Filesystem();
            if ($fs->exists($completePath) && is_dir($completePath)) {
                return $this->get('app.svc.download')->serveDirectory($completePath);
            } else if ($fs->exists($completePath)) {
                return $this->get('app.svc.download')->serveFile($completePath, $request->query->get('width'));
            } else {
                return $this->get('app.svc.response')->htmlDoesNotExist($page);
            }
        } else {
            return $this->get('app.svc.response')->htmlUnableToAccessContent($page);
        }
    }
}



