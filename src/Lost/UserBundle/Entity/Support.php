<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="support")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\SupportRepository")
 */

class Support {

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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="supports")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="SupportCategory", inversedBy="supports")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $supportCategory;	
    
    
    /**
     * @ORM\Column(name="email", type="string", length=255)
     *
     */
    protected $email;
    
    
    /**
     * @ORM\Column(name="subject", type="string", length=255)
     *
     */
    protected $subject;
    
    /**
     * @ORM\Column(name="description", type="text")
     */
    protected $description;
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('New', 'InProgress', 'Resolved')", options={"default":"New"})
     */
    protected $status = 'New';
    
    /**
     * @ORM\Column(name="email_status", type="string", columnDefinition="ENUM('NotActive', 'Active', 'Sending', 'Sent')", options={"default":"NotActive"})
     */
    protected $emailStatus = 'NotActive';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;        

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
     * Set email
     *
     * @param string $email
     * @return Support
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
     * Set subject
     *
     * @param string $subject
     * @return Support
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Support
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Support
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
     * Set emailStatus
     *
     * @param string $emailStatus
     * @return Support
     */
    public function setEmailStatus($emailStatus)
    {
        $this->emailStatus = $emailStatus;

        return $this;
    }

    /**
     * Get emailStatus
     *
     * @return string 
     */
    public function getEmailStatus()
    {
        return $this->emailStatus;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Support
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
     * Set user
     *
     * @param \Lost\UserBundle\Entity\User $user
     * @return Support
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
     * Set supportCategory
     *
     * @param \Lost\UserBundle\Entity\SupportCategory $supportCategory
     * @return Support
     */
    public function setSupportCategory(\Lost\UserBundle\Entity\SupportCategory $supportCategory = null)
    {
        $this->supportCategory = $supportCategory;

        return $this;
    }

    /**
     * Get supportCategory
     *
     * @return \Lost\UserBundle\Entity\SupportCategory 
     */
    public function getSupportCategory()
    {
        return $this->supportCategory;
    }
}
