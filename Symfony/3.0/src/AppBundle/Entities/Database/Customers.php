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
 * Customers
 *
 * @ORM\Table(name="CUSTOMERS")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\CustomersRepository")
 */
class Customers
{
    /**
     * @var integer
     *
     * @ORM\Column(name="CUS_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $cusId;

    /**
     * @var string
     *
     * @ORM\Column(name="NAME", type="string", length=50)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="STYLE_BG_COLOR", type="string", length=7)
     */
    private $styleBgColor;

    /**
     * @var string
     *
     * @ORM\Column(name="STYLE_BG_LOGO", type="string", length=250)
     */
    private $styleBgLogo;

    /**
     * Get id
     *
     * @return integer
     */
    public function getCusId()
    {
        return $this->cusId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Customers
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set styleBgColor
     *
     * @param string $styleBgColor
     * @return Customers
     */
    public function setStyleBgColor($styleBgColor)
    {
        $this->styleBgColor = $styleBgColor;

        return $this;
    }

    /**
     * Get styleBgColor
     *
     * @return string
     */
    public function getStyleBgColor()
    {
        return $this->styleBgColor;
    }

    /**
     * Set styleBgLogo
     *
     * @param string $styleBgLogo
     * @return Customers
     */
    public function setStyleBgLogo($styleBgLogo)
    {
        $this->styleBgLogo = $styleBgLogo;

        return $this;
    }

    /**
     * Get styleBgLogo
     *
     * @return string
     */
    public function getStyleBgLogo()
    {
        return $this->styleBgLogo;
    }

}
