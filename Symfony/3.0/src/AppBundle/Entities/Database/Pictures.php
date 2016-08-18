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
 * Pictures
 *
 * @ORM\Table(name="PICTURES")
 * @ORM\Entity(repositoryClass="AppBundle\Entities\Database\PicturesRepository")
 */
class Pictures
{
    /**
     * @var integer
     *
     * @ORM\Column(name="PIC_ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $picId;

    /**
     * @var integer
     *
     * @ORM\Column(name="SOURCEID", type="integer")
     */
    private $sourceid;

    /**
     * @var string
     *
     * @ORM\Column(name="PICTURE_NAME", type="string", length=255)
     */
    private $pictureName;

    /**
     * @var string
     *
     * @ORM\Column(name="COMMENT", type="text")
     */
    private $comment;


    /**
     * Get id
     *
     * @return integer
     */
    public function getPicId()
    {
        return $this->picId;
    }

    /**
     * Set sourceid
     *
     * @param integer $sourceid
     * @return Pictures
     */
    public function setSourceid($sourceid)
    {
        $this->sourceid = $sourceid;

        return $this;
    }

    /**
     * Get sourceid
     *
     * @return integer
     */
    public function getSourceid()
    {
        return $this->sourceid;
    }

    /**
     * Set pictureName
     *
     * @param string $pictureName
     * @return Pictures
     */
    public function setPictureName($pictureName)
    {
        $this->pictureName = $pictureName;

        return $this;
    }

    /**
     * Get pictureName
     *
     * @return string
     */
    public function getPictureName()
    {
        return $this->pictureName;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Pictures
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
}
