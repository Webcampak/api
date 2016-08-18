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
 * GroupsPermissions
 *
 * @ORM\Table(name="GROUPS_PERMISSIONS")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\GroupsPermissionsRepository")
 */
class GroupsPermissions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="GROPER_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $groperId;

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
     * @var \AppBundle\Entities\Database\Permissions
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Permissions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="PER_ID", referencedColumnName="PER_ID")
     * })
     */
    private $per;

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
    public function getGroperId()
    {
        return $this->groperId;
    }

    /**
     * Set access
     *
     * @param integer $access
     * @return GroupsPermissions
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
     * @return GroupsPermissions
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
     * Set per
     *
     * @param \AppBundle\Entities\Database\Permissions $per
     * @return GroupsPermissions
     */
    public function setPag(\AppBundle\Entities\Database\Permissions $per = null)
    {
        $this->per = $per;

        return $this;
    }

    /**
     * Get per
     *
     * @return \AppBundle\Entities\Database\Permissions
     */
    public function getPer()
    {
        return $this->per;
    }

    /**
     * Set per
     *
     * @param \AppBundle\Entities\Database\Permissions $per
     * @return GroupsPermissions
     */
    public function setPer(\AppBundle\Entities\Database\Permissions $per = null)
    {
        $this->per = $per;

        return $this;
    }
}
