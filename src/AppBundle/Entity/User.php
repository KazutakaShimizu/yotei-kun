<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class User
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @var \AppBundle\Entity\ScheduleSetting
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ScheduleSetting", mappedBy="user", cascade={"persist", "merge", "remove"}, orphanRemoval=true, fetch="EAGER")
     */
    private $scheduleSetting;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->scheduleSetting = new \Doctrine\Common\Collections\ArrayCollection();
    }
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return UserPlan
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return UserPlan
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     *
     * @param ScheduleSetting $ScheduleSetting
     * @return User
     */
    public function addScheduleSetting(\AppBundle\Entity\ScheduleSetting $scheduleSetting)
    {
        $scheduleSetting->setUser($this);
        $this->scheduleSetting[] = $scheduleSetting;
        return $this;
    }

    /**
     * @param ScheduleSetting $ScheduleSetting
     * @return $this
     */
    public function removeScheduleSetting(\AppBundle\Entity\ScheduleSetting $scheduleSetting)
    {
        $this->scheduleSetting->removeElement($scheduleSetting);
        return $this;
    }

    /**
     *
     * @return \Doctrine\Common\Collections\ArrayCollection|ScheduleSetting
     */
    public function getScheduleSetting()
    {
        return $this->scheduleSetting;
    }

}

