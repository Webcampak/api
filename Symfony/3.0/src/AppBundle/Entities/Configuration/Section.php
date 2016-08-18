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
namespace AppBundle\Entities\Configuration;

use JMS\Serializer\Annotation as JMS;

class Section
{
    /**
     * @JMS\Type("string")
     */
    private $name;
    /**
     * @JMS\Type("string")
     */
    private $permission;
    /**
     * @JMS\Type("ArrayCollection<AppBundle\Entities\Configuration\Parameter>")
     */
    private $parameters;
    /**
     * @JMS\Type("Configuration")
     */
    private $configuration;


    // Getters
    public function getName()
    {
        return $this->name;
    }

    // Setters
    public function setName($name)
    {
        $this->name = $name;
    }

    // Getters
    public function getPermission()
    {
        return $this->permission;
    }

    // Setters
    public function setPermission($permission)
    {
        $this->permission = $permission;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

}
