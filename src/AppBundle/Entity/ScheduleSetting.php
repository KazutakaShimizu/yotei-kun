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
 * @ORM\Table(name="schedule_setting", indexes={@ORM\Index(name="schedule_setting_id_idx", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ScheduleSettingRepository")
 */
class ScheduleSetting
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
     * @var string
     *
     * @ORM\Column(name="day_from", type="date", nullable=false)
     */
    private $dayFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="day_to", type="date", nullable=false)
     */
    private $dayTo;

    /**
     * @var string
     *
     * @ORM\Column(name="time_from", type="time", nullable=false)
     */
    private $timeFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="time_to", type="time", nullable=false)
     */
    private $timeTo;

    /**
     * @var string
     *
     * @ORM\Column(name="minimum_unit", type="integer", nullable=false)
     */
    private $minimumUnit;

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
     * @var \AppBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="scheduleSetting", fetch="EAGER", cascade={"persist", "merge"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

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
     * Set DayFrom
     *
     * @param \DateTime $DayFrom
     *
     * @return UserPlan
     */
    public function setDayFrom($dayFrom)
    {
        $this->dayFrom = $dayFrom;

        return $this;
    }

    /**
     * Get DayFrom
     *
     * @return \DateTime
     */
    public function getDayFrom()
    {
        return $this->dayFrom;
    }

    /**
     * Set DayTo
     *
     * @param \DateTime $DayTo
     *
     * @return UserPlan
     */
    public function setDayTo($dayTo)
    {
        $this->dayTo = $dayTo;

        return $this;
    }

    /**
     * Get DayTo
     *
     * @return \DateTime
     */
    public function getDayTo()
    {
        return $this->dayTo;
    }




    /**
     * Set timeFrom
     *
     * @param \DateTime $timeFrom
     *
     * @return UserPlan
     */
    public function setTimeFrom($timeFrom)
    {
        $this->timeFrom = $timeFrom;

        return $this;
    }

    /**
     * Get timeFrom
     *
     * @return \DateTime
     */
    public function getTimeFrom()
    {
        return $this->timeFrom;
    }

    /**
     * Set timeTo
     *
     * @param \DateTime $timeTo
     *
     * @return UserPlan
     */
    public function setTimeTo($timeTo)
    {
        $this->timeTo = $timeTo;

        return $this;
    }

    /**
     * Get timeTo
     *
     * @return \DateTime
     */
    public function getTimeTo()
    {
        return $this->dayTo;
    }


    /**
     * Set MinimumUnit
     *
     * @param \DateTime $minimumUnit
     *
     * @return UserPlan
     */
    public function setMinimumUnit($minimumUnit)
    {
        $this->minimumUnit = $minimumUnit;

        return $this;
    }

    /**
     * Get MinimumUnit
     *
     * @return \DateTime
     */
    public function getMinimumUnit()
    {
        return $this->minimumUnit;
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
}

