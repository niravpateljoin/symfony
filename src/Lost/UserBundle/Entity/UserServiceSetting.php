<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="user_service_setting")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\UserServiceSettingRepository")
 */
class UserServiceSetting
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;
    
    /**
     * @ORM\Column(name="service_status", type="string", columnDefinition="ENUM('Enabled', 'Disabled')", options={"default":"Enabled", "comment":"Enabled, Disabled"})
     */
    protected $serviceStatus = 'Enabled';
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;


    

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
     * Set serviceStatus
     *
     * @param string $serviceStatus
     * @return UserServiceSetting
     */
    public function setServiceStatus($serviceStatus)
    {
        $this->serviceStatus = $serviceStatus;

        return $this;
    }

    /**
     * Get serviceStatus
     *
     * @return string 
     */
    public function getServiceStatus()
    {
        return $this->serviceStatus;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserServiceSetting
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
     * @return UserServiceSetting
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
     * Set user
     *
     * @param \Lost\UserBundle\Entity\User $user
     * @return UserServiceSetting
     */
    public function setUser(\Lost\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Lost\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set service
     *
     * @param \Lost\UserBundle\Entity\Service $service
     * @return UserServiceSetting
     */
    public function setService(\Lost\UserBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Lost\UserBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }
}
