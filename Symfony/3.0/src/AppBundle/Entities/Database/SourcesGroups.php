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
 * SourcesGroups
 *
 * @ORM\Table(name="SOURCES_GROUPS")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\SourcesGroupsRepository")
 */
class SourcesGroups
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SOUGRO_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $sougroId;

    /**
     * @var string
     *
     * @ORM\Column(name="USERNAME", type="string", length=100)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="USERGROUP", type="string", length=100)
     */
    private $usergroup;

    /**
     * @var string
     *
     * @ORM\Column(name="PERMISSION", type="string", length=255)
     */
    private $permission;


    /**
     * Get id
     *
     * @return integer
     */
    public function getSougroId()
    {
        return $this->sougroId;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return SourcesGroups
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set usergroup
     *
     * @param string $usergroup
     * @return SourcesGroups
     */
    public function setUsergroup($usergroup)
    {
        $this->usergroup = $usergroup;

        return $this;
    }

    /**
     * Get usergroup
     *
     * @return string
     */
    public function getUsergroup()
    {
        return $this->usergroup;
    }

    /**
     * Set permission
     *
     * @param string $permission
     * @return SourcesGroups
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission
     *
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }
}
