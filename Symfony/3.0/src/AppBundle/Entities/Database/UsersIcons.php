<?php

namespace AppBundle\Entities\Database;

use Doctrine\ORM\Mapping as ORM;

/**
 * UsersIcons
 *
 * @ORM\Table(name="USERS_ICONS")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\UsersIconsRepository")
 */
class UsersIcons
{
    /**
     * @var integer
     *
     * @ORM\Column(name="USEICO_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $useicoId;

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
     * @var \AppBundle\Entities\Database\Applications
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Applications")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="APP_ID", referencedColumnName="APP_ID")
     * })
     */
    private $app;

    /**
     * @var string
     *
     * @ORM\Column(name="ICON_VISIBLE_FLAG", type="string", length=1, nullable=true)
     */
    private $iconVisibleFlag;

    /**
     * @var integer
     *
     * @ORM\Column(name="ICON_X_COORDINATE", type="integer", nullable=true)
     */
    private $iconXCoordinate;

    /**
     * @var integer
     *
     * @ORM\Column(name="ICON_Y_COORDINATE", type="integer", nullable=true)
     */
    private $iconYCoordinate;

    /**
     * Get id
     *
     * @return integer
     */
    public function getUseicoId()
    {
        return $this->useicoId;
    }

    /**
     * Set use
     *
     * @param \AppBundle\Entities\Database\Users $use
     * @return UsersIcons
     */
    public function setUse(\AppBundle\Entities\Database\Users $use = null)
    {
        $this->use = $use;

        return $this;
    }

    /**
     * Get use
     *
     * @return \AppBundle\Entities\Database\Users
     */
    public function getUse()
    {
        return $this->use;
    }

    /**
     * Set app
     *
     * @param \AppBundle\Entities\Database\Applications $app
     * @return UsersIcons
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

    /**
     * Set iconVisibleFlag
     *
     * @param string $iconVisibleFlag
     * @return UsersIcons
     */
    public function setIconVisibleFlag($iconVisibleFlag)
    {
        $this->iconVisibleFlag = $iconVisibleFlag;

        return $this;
    }

    /**
     * Get iconVisibleFlag
     *
     * @return string
     */
    public function getIconVisibleFlag()
    {
        return $this->iconVisibleFlag;
    }

    /**
     * Set iconXCoordinate
     *
     * @param string $iconXCoordinate
     * @return UsersIcons
     */
    public function setIconXCoordinate($iconXCoordinate)
    {
        $this->iconXCoordinate = $iconXCoordinate;

        return $this;
    }

    /**
     * Get iconXCoordinate
     *
     * @return string
     */
    public function getIconXCoordinate()
    {
        return $this->iconXCoordinate;
    }

    /**
     * Set iconYCoordinate
     *
     * @param string $iconYCoordinate
     * @return UsersIcons
     */
    public function setIconYCoordinate($iconYCoordinate)
    {
        $this->iconYCoordinate = $iconYCoordinate;

        return $this;
    }

    /**
     * Get iconYCoordinate
     *
     * @return string
     */
    public function getIconYCoordinate()
    {
        return $this->iconYCoordinate;
    }

}
