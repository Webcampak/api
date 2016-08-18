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

namespace AppBundle\Entities\Database;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupsApplications
 *
 * @ORM\Table(name="GROUPS_APPLICATIONS")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\GroupsApplicationsRepository")
 */
class GroupsApplications
{
    /**
     * @var integer
     *
     * @ORM\Column(name="GROAPP_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $groappId;

    /**
     * @var \AppBundle\Entities\Database\Groups
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Groups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="GRO_ID", referencedColumnName="GRO_ID")
     * })
     */
    private $gro;

    /**
     * @var \AppBundle\Entities\Database\Applications
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Applications")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="APP_ID", referencedColumnName="APP_ID")
     * })
     */
    private $app;

    /**
     * @var integer
     *
     * @ORM\Column(name="ACCESS", type="integer", nullable=true)
     */
    private $access;


    /**
     * Get id
     *
     * @return integer
     */
    public function getGroappId()
    {
        return $this->groappId;
    }

    /**
     * Set access
     *
     * @param integer $access
     * @return GroupsApplications
     */
    public function setAccess($access)
    {
        $this->access = $access;

        return $this;
    }

    /**
     * Get access
     *
     * @return integer
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Set gro
     *
     * @param \AppBundle\Entities\Database\Groups $gro
     * @return GroupsApplications
     */
    public function setGro(\AppBundle\Entities\Database\Groups $gro = null)
    {
        $this->gro = $gro;

        return $this;
    }

    /**
     * Get gro
     *
     * @return \AppBundle\Entities\Database\Groups
     */
    public function getGro()
    {
        return $this->gro;
    }

    /**
     * Set pag
     *
     * @param \AppBundle\Entities\Database\Applications $app
     * @return GroupsApplications
     */
    public function setApp(\AppBundle\Entities\Database\Applications $app = null)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Get app
     *
     * @return \AppBundle\Entities\Database\Applications
     */
    public function getApp()
    {
        return $this->app;
    }
}
