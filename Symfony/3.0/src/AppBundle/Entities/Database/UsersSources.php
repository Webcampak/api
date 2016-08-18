<?php

namespace AppBundle\Entities\Database;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersSources
 *
 * @ORM\Table(name="USERS_SOURCES")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\UsersSourcesRepository")
 */
class UsersSources
{
    /**
     * @var integer
     *
     * @ORM\Column(name="USESOU_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $usesouId;

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
     * @var \AppBundle\Entities\Database\Sources
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Sources")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SOU_ID", referencedColumnName="SOU_ID")
     * })
     */
    private $sou;

    /**
     * @var string
     *
     * @ORM\Column(name="ALERTS_FLAG", type="string", length=1)
     */
    private $alertsFlag;    
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getUsesouId()
    {
        return $this->usesouId;
    }

    /**
     * Set use
     *
     * @param \AppBundle\Entities\Database\Users $use
     * @return UsersSources
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

    /**
     * Set sou
     *
     * @param \AppBundle\Entities\Database\Sources $sou
     * @return UsersSources
     */
    public function setSou(\AppBundle\Entities\Database\Sources $sou = null)
    {
        $this->sou = $sou;

        return $this;
    }

    /**
     * Get sou
     *
     * @return \AppBundle\Entities\Database\Sources
     */
    public function getSou()
    {
        return $this->sou;
    }
    
    /**
     * Set alertsFlag
     *
     * @param string $alertsFlag
     * @return Users
     */
    public function setAlertsFlag($alertsFlag)
    {
        $this->alertsFlag = $alertsFlag;

        return $this;
    }

    /**
     * Get alertsFlag
     *
     * @return string
     */
    public function getAlertsFlag()
    {
        return $this->alertsFlag;
    }
    
}
