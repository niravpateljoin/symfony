<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="compensation")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\CompensationRepository")
 */
class Compensation
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="admin_id", type="integer", nullable=true)
     */
    protected $admin_id;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="isp_hours", type="integer", nullable=true)
     */
    protected $ispHours;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="iptv_days", type="integer", nullable=true)
     */
    protected $iptvDays;
    
    /**
     * @ORM\ManyToMany(targetEntity="Service")
     * @ORM\JoinTable(name="compensation_services",
     *      joinColumns={@ORM\JoinColumn(name="compensation_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="service_id", referencedColumnName="id")}
     * )
     */
    protected $services;
    
    /**
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="compensation_customers",
     *      joinColumns={@ORM\JoinColumn(name="compensation_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    protected $users;
    
    /**
     * @ORM\ManyToMany(targetEntity="Lost\AdminBundle\Entity\ServiceLocation")
     * @ORM\JoinTable(name="compensation_service_locations",
     *      joinColumns={@ORM\JoinColumn(name="compensation_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="service_location_id", referencedColumnName="id")}
     * )
     */
    protected $serviceLocations;
    
    /**
     * @ORM\Column(name="type", type="string", columnDefinition="ENUM('ServiceLocation', 'Customer')", options={"default":"", "comment":"ServiceLocation, Customer"})
     */
    protected $type;
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Queued', 'Inprogress', 'Completed')", options={"default":"Queued", "comment":"Queued, Inprogress, Completed"})
     */
    protected $status = 'Queued';
    
    /**
     * @ORM\Column(name="is_active", type="boolean", nullable=false, options={"default":true})
     */
    protected $isActive = true;
    
    /**
     * @ORM\Column(name="is_email_active", type="boolean", nullable=false, options={"default":false})
     */
    protected $isEmailActive = true;
    
    /**
     * @var string
     *
     * @ORM\Column(name="email_subject", type="string", length=255, nullable=true)
     */
    protected $emailSubject;
    
    /**
     * @var text
     * @ORM\Column(name="email_content", type="text", nullable=true)
     */
    protected $emailContent;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param string $title
     * @return Compensation
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set admin_id
     *
     * @param integer $adminId
     * @return Compensation
     */
    public function setAdminId($adminId)
    {
        $this->admin_id = $adminId;

        return $this;
    }

    /**
     * Get admin_id
     *
     * @return integer 
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * Set ispHours
     *
     * @param integer $ispHours
     * @return Compensation
     */
    public function setIspHours($ispHours)
    {
        $this->ispHours = $ispHours;

        return $this;
    }

    /**
     * Get ispHours
     *
     * @return integer 
     */
    public function getIspHours()
    {
        return $this->ispHours;
    }

    /**
     * Set iptvDays
     *
     * @param integer $iptvDays
     * @return Compensation
     */
    public function setIptvDays($iptvDays)
    {
        $this->iptvDays = $iptvDays;

        return $this;
    }

    /**
     * Get iptvDays
     *
     * @return integer 
     */
    public function getIptvDays()
    {
        return $this->iptvDays;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Compensation
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Compensation
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return Compensation
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isEmailActive
     *
     * @param boolean $isEmailActive
     * @return Compensation
     */
    public function setIsEmailActive($isEmailActive)
    {
        $this->isEmailActive = $isEmailActive;

        return $this;
    }

    /**
     * Get isEmailActive
     *
     * @return boolean 
     */
    public function getIsEmailActive()
    {
        return $this->isEmailActive;
    }

    /**
     * Set emailSubject
     *
     * @param string $emailSubject
     * @return Compensation
     */
    public function setEmailSubject($emailSubject)
    {
        $this->emailSubject = $emailSubject;

        return $this;
    }

    /**
     * Get emailSubject
     *
     * @return string 
     */
    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    /**
     * Set emailContent
     *
     * @param string $emailContent
     * @return Compensation
     */
    public function setEmailContent($emailContent)
    {
        $this->emailContent = $emailContent;

        return $this;
    }

    /**
     * Get emailContent
     *
     * @return string 
     */
    public function getEmailContent()
    {
        return $this->emailContent;
    }

    /**
     * Add services
     *
     * @param \Lost\UserBundle\Entity\Service $services
     * @return Compensation
     */
    public function addService(\Lost\UserBundle\Entity\Service $services)
    {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param \Lost\UserBundle\Entity\Service $services
     */
    public function removeService(\Lost\UserBundle\Entity\Service $services)
    {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Add users
     *
     * @param \Lost\UserBundle\Entity\User $users
     * @return Compensation
     */
    public function addUser(\Lost\UserBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Lost\UserBundle\Entity\User $users
     */
    public function removeUser(\Lost\UserBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add serviceLocations
     *
     * @param \Lost\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return Compensation
     */
    public function addServiceLocation(\Lost\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations[] = $serviceLocations;

        return $this;
    }

    /**
     * Remove serviceLocations
     *
     * @param \Lost\AdminBundle\Entity\ServiceLocation $serviceLocations
     */
    public function removeServiceLocation(\Lost\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations->removeElement($serviceLocations);
    }

    /**
     * Get serviceLocations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocations()
    {
        return $this->serviceLocations;
    }
}
