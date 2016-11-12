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
 * Sources
 *
 * @ORM\Table(name="SOURCES")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\SourcesRepository")
 */
class Sources
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SOU_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $souId;

    /**
     * @ORM\OneToMany(targetEntity="UsersSources", mappedBy="sou", cascade={"remove"})
     */
    protected $usesou;

    /**
     * @var integer
     *
     * @ORM\Column(name="SOURCEID", type="integer")
     */
    private $sourceId;

    /**
     * @var string
     *
     * @ORM\Column(name="NAME", type="string", length=255)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="WEIGHT", type="integer")
     */
    private $weight;

    /**
     * @var integer
     *
     * @ORM\Column(name="QUOTA", type="integer", nullable=true)
     */
    private $quota;
    
    /**
     * @var string
     *
     * @ORM\Column(name="REMOTE_HOST", type="string", length=255, nullable=true)
     */
    private $remoteHost;

    /**
     * @var string
     *
     * @ORM\Column(name="REMOTE_PASSWORD", type="string", length=255, nullable=true)
     */
    private $remotePassword;

    /**
     * @var string
     *
     * @ORM\Column(name="REMOTE_USERNAME", type="string", length=255, nullable=true)
     */
    private $remoteUsername;

    /**
     * Get id
     *
     * @return integer
     */
    public function getSouId()
    {
        return $this->souId;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     * @return Sources
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * Set quota
     *
     * @param integer $quota
     * @return Sources
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;

        return $this;
    }

    /**
     * Get quota
     *
     * @return integer
     */
    public function getQuota()
    {
        return $this->quota;
    }    
    
    /**
     * Set name
     *
     * @param string $name
     * @return Sources
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
     * Set weight
     *
     * @param integer $weight
     * @return Sources
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set remoteHost
     *
     * @param string $remoteHost
     * @return Sources
     */
    public function setRemoteHost($remoteHost)
    {
        $this->remoteHost = $remoteHost;

        return $this;
    }

    /**
     * Get remoteHost
     *
     * @return string
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    /**
     * Set remotePassword
     *
     * @param string $remotePassword
     * @return Sources
     */
    public function setRemotePassword($remotePassword)
    {
        $this->remotePassword = $remotePassword;

        return $this;
    }

    /**
     * Get remotePassword
     *
     * @return string
     */
    public function getRemotePassword()
    {
        return $this->remotePassword;
    }

    /**
     * Set remoteUsername
     *
     * @param string $remoteUsername
     * @return Sources
     */
    public function setRemoteUsername($remoteUsername)
    {
        $this->remoteUsername = $remoteUsername;

        return $this;
    }

    /**
     * Get remoteUsername
     *
     * @return string
     */
    public function getRemoteUsername()
    {
        return $this->remoteUsername;
    }

    public function updateSourceEntity($inputParams)
    {
        if (isset($inputParams['NAME'])) {
            $this->setName($inputParams['NAME']);
        }
        if (isset($inputParams['SOURCEID'])) {
            $this->setSourceId($inputParams['SOURCEID']);
        }
        if (isset($inputParams['QUOTA'])) {
            $this->setQuota($inputParams['QUOTA']);
        }        
        if (isset($inputParams['WEIGHT'])) {
            $this->setWeight($inputParams['WEIGHT']);
        }
        if (isset($inputParams['REMOTE_HOST'])) {
            $this->setRemoteHost($inputParams['REMOTE_HOST']);
        }
        if (isset($inputParams['REMOTE_USERNAME'])) {
            $this->setRemoteUsername($inputParams['REMOTE_USERNAME']);
        }
        if (isset($inputParams['REMOTE_PASSWORD'])) {
            $this->setRemotePassword($inputParams['REMOTE_PASSWORD']);
        }
    }
}
