<?php

namespace Lost\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Lost\UserBundle\Entity\Service;

/**
 * ServiceLocation
 *
 * @ORM\Table(name="service_location")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Lost\AdminBundle\Repository\ServiceLocationRepository")
 */
class ServiceLocation
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;
    
    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;
    
    /**
     * @ORM\ManyToOne(targetEntity="Lost\UserBundle\Entity\Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     */
    protected $country;
        
    /**
     * @ORM\OneToMany(targetEntity="IpAddressZone", mappedBy="serviceLocation", cascade={"persist", "remove"})
     */
    protected $ipAddressZones;
    
    /**
     * @ORM\OneToMany(targetEntity="ServiceLocationDiscount", mappedBy="serviceLocation", cascade={"persist", "remove"})
     */
    protected $serviceLocationDiscounts;
    
     /**
        * @ORM\ManyToMany(targetEntity="Lost\UserBundle\Entity\User", mappedBy="serviceLocations")
	*
	*/
    protected $admins;
    
    /**
     * @ORM\ManyToMany(targetEntity="Lost\UserBundle\Entity\Compensation", mappedBy="serviceLocations")
     *
     */
    protected $compensations;
                
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ipAddressZones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocationDiscounts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->admins = new \Doctrine\Common\Collections\ArrayCollection();
        $this->compensations = new \Doctrine\Common\Collections\ArrayCollection();
        
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
     * Set name
     *
     * @param string $name
     * @return ServiceLocation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ServiceLocation
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
     * Set country
     *
     * @param \Lost\UserBundle\Entity\Country $country
     * @return ServiceLocation
     */
    public function setCountry(\Lost\UserBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Lost\UserBundle\Entity\Country 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Add ipAddressZones
     *
     * @param \Lost\AdminBundle\Entity\IpAddressZone $ipAddressZones
     * @return ServiceLocation
     */
    public function addIpAddressZone(\Lost\AdminBundle\Entity\IpAddressZone $ipAddressZones)
    {
        $this->ipAddressZones[] = $ipAddressZones;

        return $this;
    }

    /**
     * Remove ipAddressZones
     *
     * @param \Lost\AdminBundle\Entity\IpAddressZone $ipAddressZones
     */
    public function removeIpAddressZone(\Lost\AdminBundle\Entity\IpAddressZone $ipAddressZones)
    {
        $this->ipAddressZones->removeElement($ipAddressZones);
    }

    /**
     * Get ipAddressZones
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getIpAddressZones()
    {
        return $this->ipAddressZones;
    }

    /**
     * Add serviceLocationDiscounts
     *
     * @param \Lost\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts
     * @return ServiceLocation
     */
    public function addServiceLocationDiscount(\Lost\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts)
    {
        $this->serviceLocationDiscounts[] = $serviceLocationDiscounts;

        return $this;
    }

    /**
     * Remove serviceLocationDiscounts
     *
     * @param \Lost\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts
     */
    public function removeServiceLocationDiscount(\Lost\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts)
    {
        $this->serviceLocationDiscounts->removeElement($serviceLocationDiscounts);
    }

    /**
     * Get serviceLocationDiscounts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocationDiscounts()
    {
        return $this->serviceLocationDiscounts;
    }

    /**
     * Add admins
     *
     * @param \Lost\UserBundle\Entity\User $admins
     * @return ServiceLocation
     */
    public function addAdmin(\Lost\UserBundle\Entity\User $admins)
    {
        $this->admins[] = $admins;

        return $this;
    }

    /**
     * Remove admins
     *
     * @param \Lost\UserBundle\Entity\User $admins
     */
    public function removeAdmin(\Lost\UserBundle\Entity\User $admins)
    {
        $this->admins->removeElement($admins);
    }

    /**
     * Get admins
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAdmins()
    {
        return $this->admins;
    }


    /**
     * Add compensations
     *
     * @param \Lost\UserBundle\Entity\Compensation $compensations
     * @return ServiceLocation
     */
    public function addCompensation(\Lost\UserBundle\Entity\Compensation $compensations)
    {
        $this->compensations[] = $compensations;

        return $this;
    }

    /**
     * Remove compensations
     *
     * @param \Lost\UserBundle\Entity\Compensation $compensations
     */
    public function removeCompensation(\Lost\UserBundle\Entity\Compensation $compensations)
    {
        $this->compensations->removeElement($compensations);
    }

    /**
     * Get compensations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCompensations()
    {
        return $this->compensations;
    }
}
