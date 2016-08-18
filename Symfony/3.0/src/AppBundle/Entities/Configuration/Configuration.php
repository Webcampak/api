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

//http://johnkary.net/blog/deserializing-xml-with-jms-serializer-bundle/
//http://jmsyst.com/libs/serializer

namespace AppBundle\Entities\Configuration;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

class Configuration
{
    /**
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @JMS\Type("ArrayCollection<AppBundle\Entities\Configuration\Section>")
     * @JMS\XmlList(entry="section")
     */
    private $sections;
    /**
     * @JMS\Type("string")
     */
    private $permission;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }


    public function getName()
    {
        return $this->name;
    }

    // Setters
    public function setName($name)
    {
        $this->name = $name;
    }

    public function setSections(array $sections)
    {
        $this->sections = $sections;
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function getPermission()
    {
        return $this->permission;
    }

    public function setPermission($permission)
    {
        $this->permission = $permission;
    }

    public function findParameterByName($parameterName)
    {
        foreach($this->sections as $section) {
            foreach($section->getParameters() as $parameter) {
                if ($parameter->getName() == $parameterName) {
                    return $parameter;
                }
            }
        }
        return false;
    }

}
