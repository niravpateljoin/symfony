<?php

namespace Lost\UserBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="permission")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\PermissionRepository")
 */

class Permission {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	
	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	protected $name;
	
	/**
	 * @ORM\Column(type="string", length=255, nullable=false)
	 */
	protected $code;
	
	/**
	 * @ORM\ManyToMany(targetEntity="Group", mappedBy="permissions")
	 *
	 */
	protected $groups;
	
	/**
	 * @ORM\ManyToOne(targetEntity="PermissionCategory", inversedBy="permissions")
	 * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
	 */
	protected $category;
	
	public function __construct() {
	    
		$this->groups = new ArrayCollection();
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
     * @return Permission
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
     * Set code
     *
     * @param string $code
     * @return Permission
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add groups
     *
     * @param \Lost\UserBundle\Entity\Group $groups
     * @return Permission
     */
    public function addGroup(\Lost\UserBundle\Entity\Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Lost\UserBundle\Entity\Group $groups
     */
    public function removeGroup(\Lost\UserBundle\Entity\Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set category
     *
     * @param \Lost\UserBundle\Entity\PermissionCategory $category
     * @return Permission
     */
    public function setCategory(\Lost\UserBundle\Entity\PermissionCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Lost\UserBundle\Entity\PermissionCategory 
     */
    public function getCategory()
    {
        return $this->category;
    }
}
