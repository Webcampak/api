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
 * UsersPreferences
 *
 * @ORM\Table(name="USERS_PREFERENCES")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\UsersPreferencesRepository")
 */
class UsersPreferences
{
    /**
     * @var integer
     *
     * @ORM\Column(name="USEPRE_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $usepreId;

    /**
     * @var string
     *
     * @ORM\Column(name="WIDGET", type="string", length=255)
     */
    private $widget;

    /**
     * @var string
     *
     * @ORM\Column(name="STATEFULCONFIG", type="text")
     */
    private $statefulconfig;

    /**
     * @var string
     *
     * @ORM\Column(name="SENCHA_APP", type="string", length=255)
     */
    private $senchaApp;

    /**
     * @var \AppBundle\Entities\Database\Users
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="USE_ID", referencedColumnName="USE_ID")
     * })
     */
    private $use;

    /**
     * Get id
     *
     * @return integer
     */
    public function getUsepreId()
    {
        return $this->usepreId;
    }

    /**
     * Set widget
     *
     * @param string $widget
     * @return UsersPreferences
     */
    public function setWidget($widget)
    {
        $this->widget = $widget;

        return $this;
    }

    /**
     * Get widget
     *
     * @return string
     */
    public function getWidget()
    {
        return $this->widget;
    }

    /**
     * Set statefulconfig
     *
     * @param string $statefulconfig
     * @return UsersPreferences
     */
    public function setStatefulconfig($statefulconfig)
    {
        $this->statefulconfig = $statefulconfig;

        return $this;
    }

    /**
     * Get statefulconfig
     *
     * @return string
     */
    public function getStatefulconfig()
    {
        return $this->statefulconfig;
    }

    /**
     * Set senchaApp
     *
     * @param string $senchaApp
     * @return UsersPreferences
     */
    public function setSenchaApp($senchaApp)
    {
        $this->senchaApp = $senchaApp;

        return $this;
    }

    /**
     * Get senchaApp
     *
     * @return string
     */
    public function getSenchaApp()
    {
        return $this->senchaApp;
    }

    /**
     * Set use
     *
     * @param \AppBundle\Entities\Database\Users $use
     * @return UsersApplications
     */
    public function setUse(\AppBundle\Entities\Database\Users $use = null)
    {
        $this->use = $use;

        return $this;
    }

    /**
     * Get gro
     *
     * @return \AppBundle\Entities\Database\Users
     */
    public function getUse()
    {
        return $this->use;
    }

}
