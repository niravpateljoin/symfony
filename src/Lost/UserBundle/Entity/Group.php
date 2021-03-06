<?php

namespace Lost\UserBundle\Entity;

use FOS\UserBundle\Model\Group as BaseGroup;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="lost_group")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\GroupRepository")
 */
class Group extends BaseGroup {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	
	/**
	 * @ORM\ManyToMany(targetEntity="User", mappedBy="groups")
	 *
	 */
	protected $users;
	
	/**
	 * @ORM\ManyToMany(targetEntity="Permission")
	 * @ORM\JoinTable(name="group_permissions",
	 *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="permission_id", referencedColumnName="id")}
	 * )
	 */
	protected $permissions;
	
	
	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}
	
    /**
     * Constructor
     */
    public function __construct()
    {
//         parent::__construct();
        $this->roles = array('ROLE_ADMIN');
        $this->users = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    /**
     * Add users
     *
     * @param \Lost\UserBundle\Entity\User $users
     * @return Group
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
     * Add permissions
     *
     * @param \Lost\UserBundle\Entity\Permission $permissions
     * @return Group
     */
    public function addPermission(\Lost\UserBundle\Entity\Permission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Lost\UserBundle\Entity\Permission $permissions
     */
    public function removePermission(\Lost\UserBundle\Entity\Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPermissions()
    {
        return $this->permissions;
    }


    public function getGroupPermissionArr() {
        $permissionArr = array();
        
        foreach($this->permissions as $permission) {
            $permissionArr[$permission->getCode()] = $permission->getCode();
        }
        
        return $permissionArr;
    }

}
