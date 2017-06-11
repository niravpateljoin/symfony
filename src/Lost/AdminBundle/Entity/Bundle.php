<?php

namespace Lost\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Lost\UserBundle\Entity\Service;
/**
 * Bundle
 *
 * @ORM\Table(name="bundle")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Lost\AdminBundle\Repository\BundleRepository")
 */
class Bundle
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
     * @ORM\Column(name="iptv", type="string", length=55, nullable=false)
     */
    protected $iptv;
    
    /**
     * @ORM\Column(name="isp", type="string", length=55, nullable=false)
     */
    protected $isp;
    
    
    /**
     * @ORM\Column(name="price", type="string", length=55, nullable=false)
     */
    protected $price;
    
    /**
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     */
    
    protected $isActive;
    

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
     * Set iptv
     *
     * @param string $iptv
     * @return Bundle
     */
    public function setIptv($iptv)
    {
        $this->iptv = $iptv;

        return $this;
    }

    /**
     * Get iptv
     *
     * @return string 
     */
    public function getIptv()
    {
        return $this->iptv;
    }

    /**
     * Set isp
     *
     * @param string $isp
     * @return Bundle
     */
    public function setIsp($isp)
    {
        $this->isp = $isp;

        return $this;
    }

    /**
     * Get isp
     *
     * @return string 
     */
    public function getIsp()
    {
        return $this->isp;
    }

    /**
     * Set price
     *
     * @param string $price
     * @return Bundle
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Bundle
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
}
