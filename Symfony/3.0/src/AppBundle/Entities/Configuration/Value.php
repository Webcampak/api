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

class Value
{
    /**
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @JMS\Type("string")
     */
    private $default;

    /**
     * @JMS\Type("string")
     */
    private $type;

    /**
     * @JMS\Type("string")
     */
    private $description;

    /**
     * @JMS\Type("AppBundle\Entities\Configuration\Parameter")
     */
    private $parameter;

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
    public function getDefault()
    {
        return $this->default;
    }

    // Setters
    public function setDefault($default)
    {
        $this->default = $default;
    }

    // Getters
    public function getType()
    {
        return $this->type;
    }

    // Setters
    public function setType($type)
    {
        $this->type = $type;
    }

    // Getters
    public function getDescription()
    {
        return $this->description;
    }

    // Setters
    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setParameter(Parameter $parameter)
    {
        $this->parameter = $parameter;
    }

    public function getParameter()
    {
        return $this->parameter;
    }
}
