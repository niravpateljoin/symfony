<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="country")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\CountryRepository")
 */
class Country
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;
    
    /**
     * @var string
     *
     * @ORM\Column(name="iso_code", type="string", length=255)
     */
    protected $isoCode;
    
    /**
     *
     * @ORM\OneToMany(targetEntity="CountrywiseService", mappedBy="country")
     */
    protected $countrywiseService;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countrywiseService = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Country
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
     * Set isoCode
     *
     * @param string $isoCode
     * @return Country
     */
    public function setIsoCode($isoCode)
    {
        $this->isoCode = $isoCode;

        return $this;
    }

    /**
     * Get isoCode
     *
     * @return string 
     */
    public function getIsoCode()
    {
        return $this->isoCode;
    }

    /**
     * Add countrywiseService
     *
     * @param \Lost\UserBundle\Entity\CountrywiseService $countrywiseService
     * @return Country
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
}
