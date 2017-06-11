<?php
// src/Lost/UserBundle/Entity/Service.php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Lost\ServiceBundle\Entity\ServicePurchase;
use Lost\ServiceBundle\Entity\Package;
use Lost\AdminBundle\Entity\IpAddressZone;

/**
 * @ORM\Entity
 * @ORM\Table(name="service")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\ServiceRepository")
 */
class Service
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
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     */
    protected $status;
    
    /**
     * @ORM\OneToMany(targetEntity="CountrywiseService", mappedBy="services")
     */
    protected $countrywiseService;
    
    /**
     * @ORM\ManyToMany(targetEntity="Compensation", mappedBy="services")
     *
     */
    protected $compensations;

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
     * @return Service
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
     * Set status
     *
     * @param boolean $status
     * @return Service
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->servicesCountrywiseService = new \Doctrine\Common\Collections\ArrayCollection();
        $this->compensations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add servicesCountrywiseService
     *
     * @param \Lost\UserBundle\Entity\CountrywiseService $servicesCountrywiseService
     * @return Service
     */
    public function addServicesCountrywiseService(\Lost\UserBundle\Entity\CountrywiseService $servicesCountrywiseService)
    {
        $this->servicesCountrywiseService[] = $servicesCountrywiseService;

        return $this;
    }

    /**
     * Remove servicesCountrywiseService
     *
     * @param \Lost\UserBundle\Entity\CountrywiseService $servicesCountrywiseService
     */
    public function removeServicesCountrywiseService(\Lost\UserBundle\Entity\CountrywiseService $servicesCountrywiseService)
    {
        $this->servicesCountrywiseService->removeElement($servicesCountrywiseService);
    }

    /**
     * Get servicesCountrywiseService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServicesCountrywiseService()
    {
        return $this->servicesCountrywiseService;
    }

    /**
     * Add countrywiseService
     *
     * @param \Lost\UserBundle\Entity\CountrywiseService $countrywiseService
     * @return Service
     */
    public function addCountrywiseService(\Lost\UserBundle\Entity\CountrywiseService $countrywiseService)
    {
        $this->countrywiseService[] = $countrywiseService;

        return $this;
    }

    /**
     * Remove countrywiseService
     *
     * @param \Lost\UserBundle\Entity\CountrywiseService $countrywiseService
     */
    public function removeCountrywiseService(\Lost\UserBundle\Entity\CountrywiseService $countrywiseService)
    {
        $this->countrywiseService->removeElement($countrywiseService);
    }

    /**
     * Get countrywiseService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCountrywiseService()
    {
        return $this->countrywiseService;
    }

    /**
     * Add compensations
     *
     * @param \Lost\UserBundle\Entity\Compensation $compensations
     * @return Service
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
