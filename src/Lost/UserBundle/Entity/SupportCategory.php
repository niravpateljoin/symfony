<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="support_category")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\SupportCategoryRepository")
 */

class SupportCategory {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
     /**
     * @ORM\Column(name="name", type="string", length=255)
     *
     */
    
    protected $name;
    
    
    /**
     * @ORM\OneToMany(targetEntity="Support", mappedBy="supportCategory")
     */    
     protected $supports;    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->supports = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return SupportCategory
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
     * Add supports
     *
     * @param \Lost\UserBundle\Entity\Support $supports
     * @return SupportCategory
     */
    public function addSupport(\Lost\UserBundle\Entity\Support $supports)
    {
        $this->supports[] = $supports;

        return $this;
    }

    /**
     * Remove supports
     *
     * @param \Lost\UserBundle\Entity\Support $supports
     */
    public function removeSupport(\Lost\UserBundle\Entity\Support $supports)
    {
        $this->supports->removeElement($supports);
    }

    /**
     * Get supports
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSupports()
    {
        return $this->supports;
    }
}
