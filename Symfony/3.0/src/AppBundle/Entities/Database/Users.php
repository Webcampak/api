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
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

use Doctrine\Common\Collections\ArrayCollection;
/**
 * Users
 *
 * @ORM\Table(name="USERS")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\UsersRepository")
 */
class Users implements AdvancedUserInterface, \Serializable, EquatableInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="USE_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $useId;

    /**
     * @var \AppBundle\Entities\Database\Customers
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Customers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CUS_ID", referencedColumnName="CUS_ID", nullable=true)
     * })
     */
    private $cus;

    /**
     * @var \AppBundle\Entities\Database\Groups
     *
     * @ORM\ManyToOne(targetEntity="\AppBundle\Entities\Database\Groups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="GRO_ID", referencedColumnName="GRO_ID", nullable=true)
     * })
     */
    private $gro;

    /**
     * @var string
     *
     * @ORM\Column(name="USERNAME", type="string", length=100)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="PASSWORD", type="string", length=255)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="SALT", type="string", length=255)
     */
    private $salt;

    /**
     * @var string
     *
     * @ORM\Column(name="CHANGE_PWD_FLAG", type="string", length=1, nullable=true)
     */
    private $changePwdFlag;

    /**
     * @var string
     *
     * @ORM\Column(name="ACTIVE_FLAG", type="string", length=1)
     */
    private $activeFlag;

    /**
     * @var string
     *
     * @ORM\Column(name="FIRSTNAME", type="string", length=100, nullable=true)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="LASTNAME", type="string", length=100, nullable=true)
     */
    private $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="EMAIL", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="LANG", type="string", length=10, nullable=true)
     */
    private $lang;

    /**
     * @var integer
     *
     * @ORM\Column(name="LAST_LOGIN", type="integer", nullable=true)
     */
    private $lastLogin;

    public function __construct($username, $password, $salt, array $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->groups = new ArrayCollection();
    }

    /**
     * Set activeFlag
     *
     * @param string $activeFlag
     * @return Users
     */
    public function setActiveFlag($activeFlag)
    {
        $this->activeFlag = $activeFlag;

        return $this;
    }

    /**
     * Get activeFlag
     *
     * @return string
     */
    public function getActiveFlag()
    {
        return $this->activeFlag;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Users
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
     * Set password
     *
     * @param string $password
     * @return Users
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return Users
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set changePwdFlag
     *
     * @param string $changePwdFlag
     * @return Users
     */
    public function setChangePwdFlag($changePwdFlag)
    {
        $this->changePwdFlag = $changePwdFlag;

        return $this;
    }

    /**
     * Get changePwdFlag
     *
     * @return string
     */
    public function getChangePwdFlag()
    {
        return $this->changePwdFlag;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Users
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Users
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Users
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set lang
     *
     * @param string $lang
     * @return Users
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set lastLogin
     *
     * @param integer $lastLogin
     * @return Users
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return integer
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Get useId
     *
     * @return integer
     */
    public function getUseId()
    {
        return $this->useId;
    }

    /**
     * Set cus
     *
     * @param \AppBundle\Entities\Database\Customers $cus
     * @return Users
     */
    public function setCus(\AppBundle\Entities\Database\Customers $cus = null)
    {
        $this->cus = $cus;

        return $this;
    }

    /**
     * Get cus
     *
     * @return \AppBundle\Entities\Database\Customers
     */
    public function getCus()
    {
        return $this->cus;
    }

    /**
     * Set gro
     *
     * @param \AppBundle\Entities\Database\Groups $gro
     * @return Users
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
     * @inheritDoc
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->useId,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->useId,
        ) = unserialize($serialized);
    }

    public function isEqualTo(UserInterface $user)
    {
        return $this->useId === $user->getUseId();
    }


    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
      if ($this->activeFlag == 'Y') {
        return true;
      } else {
        return false;
      }
    }

    public function updateUserEntity($inputParams)
    {
        if (isset($inputParams['CUS'])) {
            $this->setCus($inputParams['CUS']);
        }
        if (isset($inputParams['GRO'])) {
            $this->setGro($inputParams['GRO']);
        }
        if (isset($inputParams['FIRSTNAME'])) {
            $this->setFirstname($inputParams['FIRSTNAME']);
        }
        if (isset($inputParams['LASTNAME'])) {
            $this->setLastname($inputParams['LASTNAME']);
        }
        if (isset($inputParams['EMAIL'])) {
            $this->setEmail($inputParams['EMAIL']);
        }
        if (isset($inputParams['ACTIVE_FLAG'])) {
            $this->setActiveFlag($inputParams['ACTIVE_FLAG']);
        }
        if (isset($inputParams['CHANGE_PWD_FLAG'])) {
            $this->setChangePwdFlag($inputParams['CHANGE_PWD_FLAG']);
        }
    }

}
