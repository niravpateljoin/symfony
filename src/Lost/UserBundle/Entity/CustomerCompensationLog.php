<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * Country
 *
 * @ORM\Table(name="customer_compensation_log")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\CustomerCompensationLogRepository")
 */
class CustomerCompensationLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $services;
    
    /**
     * @ORM\ManyToOne(targetEntity="Compensation")
     * @ORM\JoinColumn(name="compensation_id", referencedColumnName="id")
     */
    protected $compensation;
    
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Failure', 'Success')", options={"default":"Failure", "comment":"Failure, Success"})
     */
 
    protected $status = 'Failure';
    
    /**
     * @var integer
     *
     * @ORM\Column(name="bonus", type="integer", length=11, nullable=true)
     */
    protected $bonus;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="api_error", type="string", length=255, nullable=true)
     */
    protected $apiError;
    
    
    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
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
     * Set status
     *
     * @param string $status
     * @return CustomerCompensationLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set bonus
     *
     * @param integer $bonus
     * @return CustomerCompensationLog
     */
    public function setBonus($bonus)
    {
        $this->bonus = $bonus;

        return $this;
    }

    /**
     * Get bonus
     *
     * @return integer 
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * Set selevisionError
     *
     * @param string $selevisionError
     * @return CustomerCompensationLog
     */
    public function setSelevisionError($selevisionError)
    {
        $this->selevisionError = $selevisionError;

        return $this;
    }

    /**
     * Get selevisionError
     *
     * @return string 
     */
    public function getSelevisionError()
    {
        return $this->selevisionError;
    }

    /**
     * Set user
     *
     * @param \Lost\UserBundle\Entity\User $user
     * @return CustomerCompensationLog
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
     * Set services
     *
     * @param \Lost\UserBundle\Entity\Service $services
     * @return CustomerCompensationLog
     */
    public function setServices(\Lost\UserBundle\Entity\Service $services = null)
    {
        $this->services = $services;

        return $this;
    }

    /**
     * Get services
     *
     * @return \Lost\UserBundle\Entity\Service 
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CustomerCompensationLog
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
     * @return CustomerCompensationLog
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
     * Set compensation
     *
     * @param \Lost\UserBundle\Entity\Compensation $compensation
     * @return CustomerCompensationLog
     */
    public function setCompensation(\Lost\UserBundle\Entity\Compensation $compensation = null)
    {
        $this->compensation = $compensation;

        return $this;
    }

    /**
     * Get compensation
     *
     * @return \Lost\UserBundle\Entity\Compensation 
     */
    public function getCompensation()
    {
        return $this->compensation;
    }

    /**
     * Set apiError
     *
     * @param string $apiError
     * @return CustomerCompensationLog
     */
    public function setApiError($apiError)
    {
        $this->apiError = $apiError;

        return $this;
    }

    /**
     * Get apiError
     *
     * @return string 
     */
    public function getApiError()
    {
        return $this->apiError;
    }
}
