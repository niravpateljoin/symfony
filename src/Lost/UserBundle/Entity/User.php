<?php

namespace Lost\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="lost_user")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\UserRepository")
 */

class User extends BaseUser {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct() {
        parent::__construct();
        $this->roles = array('ROLE_USER');
        $this->groups = new ArrayCollection();
        $this->serviceLocations = new ArrayCollection();
        $this->compensations = new ArrayCollection();
        
        
    }

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $lastname;

    /**
     * @ORM\Column(name="ip_address", type="string", length=15, nullable=true)
     */
    protected $ipAddress;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $address;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $state;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $zip;

    /**
     * @var string
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    protected $phone;

    /**
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     */
    protected $country;

    /**
     * @ORM\Column(name="user_type", type="string", columnDefinition="enum('US Military', 'US Government', 'Civilian')", options={"default":"US Military"} )
     */
    protected $userType = 'US Military';

    /**
     * @ORM\Column(name="is_email_verified", type="boolean", nullable=true)
     */
    protected $isEmailVerified = false;

    /**
     * @ORM\Column(name="email_verification_date", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $emailVerificationDate;

    /**
     * @ORM\Column(name="is_email_optout", type="boolean", nullable=true)
     */
    protected $isEmailOptout = false;

    /**
     * @ORM\Column(name="is_loggedin", type="boolean", nullable=false, options={"default":false})
     */
    protected $isloggedin = false;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(name="is_deleted", type="boolean", nullable=true)
     */
    protected $isDeleted = false;

    /**
     * @ORM\Column(name="is_deers_authenticated", type="boolean", nullable=true)
     */
    protected $isDeersAuthenticated = false;

    /**
     * @ORM\Column(name="deers_authenticated_at", type="datetime", nullable=true)
     */
    protected $deersAuthenticatedAt;

    /**
     * @ORM\OneToMany(targetEntity="UserService", mappedBy="user")
     */
    protected $userServices;
    
    /**
     * @ORM\OneToOne(targetEntity="UserSetting", mappedBy="user")
     */
    protected $userSetting;
    
    /**
     * @ORM\Column(name="is_iptv_disabled", type="boolean", nullable=true)
     */
    protected $isIptvDisabled = false;

    /**
     * @ORM\Column(name="new_selevision_user", type="boolean", nullable=true)
     */
    protected $newSelevisionUser = false;

    /**
     * @ORM\OneToMany(targetEntity="UserServiceSetting", mappedBy="user")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    protected $serviceSettings;
    
    /**
     * @ORM\Column(name="is_last_login", type="boolean", nullable=false, options={"default":false})
     */
    protected $isLastLogin = false;
    
    /**
     * @ORM\Column(name="ip_address_long", type="bigint", nullable=true)
     */
    protected $ipAddressLong;
    
    /**
     * @ORM\Column(name="cid", type="bigint", nullable=true)
     */
    protected $cid;
    
     /**
     * @ORM\OneToMany(targetEntity="UserMacAddress", mappedBy="user")
     */
    private $userMacAddress;


   /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="lost_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;
    
     /**
	 * @ORM\ManyToMany(targetEntity="Lost\AdminBundle\Entity\ServiceLocation")
	 * @ORM\JoinTable(name="admin_service_location",
	 *      joinColumns={@ORM\JoinColumn(name="admin_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="service_location_id", referencedColumnName="id")}
	 * )
	 */
    protected $serviceLocations;
    
    /**
     * @ORM\ManyToMany(targetEntity="Compensation", mappedBy="users")
     *
     */
    protected $compensations;
    
    /**
     * @ORM\OneToOne(targetEntity="UserCredit" , mappedBy="user")
     */
    protected $userCredit;
    

    
    public function getActiveServices() {
        $activeServices = array();
        foreach ($this->getUserServices() as $record) {
            if ($record->getStatus() == 1) {
                $activeServices[$record->getService()->getId()]['name'] = $record->getService()->getName();
            }
        }
        return $activeServices;
    }

    public function getActivePackages() {

        $activePackages = array();
        foreach ($this->getUserServices() as $record) {

            if ($record->getStatus() == 1) {
                $temp = array();

                $temp['packageId'] = $record->getPackageId();
                $temp['packageName'] = $record->getPackageName();
                $temp['amount'] = $record->getAmount();

                $activePackages[] = $temp;
            }
        }
        return $activePackages;
    }

    public function getActivePackageIds() {
        $activePackageIds = array();
        foreach ($this->getUserServices() as $record) {

            if ($record->getStatus() == 1) {
                $activePackageIds[] = $record->getPackageId();
            }
        }
        return $activePackageIds;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname($firstname) {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname) {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return User
     */
    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return User
     */
    public function setAddress($address) {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return User
     */
    public function setCity($city) {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return User
     */
    public function setState($state) {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Set zip
     *
     * @param string $zip
     * @return User
     */
    public function setZip($zip) {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string 
     */
    public function getZip() {
        return $this->zip;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return User
     */
    public function setPhone($phone) {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * Set userType
     *
     * @param string $userType
     * @return User
     */
    public function setUserType($userType) {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return string 
     */
    public function getUserType() {
        return $this->userType;
    }

    /**
     * Set isEmailVerified
     *
     * @param boolean $isEmailVerified
     * @return User
     */
    public function setIsEmailVerified($isEmailVerified) {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    /**
     * Get isEmailVerified
     *
     * @return boolean 
     */
    public function getIsEmailVerified() {
        return $this->isEmailVerified;
    }

    /**
     * Set emailVerificationDate
     *
     * @param \DateTime $emailVerificationDate
     * @return User
     */
    public function setEmailVerificationDate($emailVerificationDate) {
        $this->emailVerificationDate = $emailVerificationDate;

        return $this;
    }

    /**
     * Get emailVerificationDate
     *
     * @return \DateTime 
     */
    public function getEmailVerificationDate() {
        return $this->emailVerificationDate;
    }

    /**
     * Set isEmailOptout
     *
     * @param boolean $isEmailOptout
     * @return User
     */
    public function setIsEmailOptout($isEmailOptout) {
        $this->isEmailOptout = $isEmailOptout;

        return $this;
    }

    /**
     * Get isEmailOptout
     *
     * @return boolean 
     */
    public function getIsEmailOptout() {
        return $this->isEmailOptout;
    }

    /**
     * Set isloggedin
     *
     * @param boolean $isloggedin
     * @return User
     */
    public function setIsloggedin($isloggedin) {
        $this->isloggedin = $isloggedin;

        return $this;
    }

    /**
     * Get isloggedin
     *
     * @return boolean 
     */
    public function getIsloggedin() {
        return $this->isloggedin;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return User
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return User
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return User
     */
    public function setIsDeleted($isDeleted) {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted() {
        return $this->isDeleted;
    }

    /**
     * Set isDeersAuthenticated
     *
     * @param boolean $isDeersAuthenticated
     * @return User
     */
    public function setIsDeersAuthenticated($isDeersAuthenticated) {
        $this->isDeersAuthenticated = $isDeersAuthenticated;

        return $this;
    }

    /**
     * Get isDeersAuthenticated
     *
     * @return boolean 
     */
    public function getIsDeersAuthenticated() {
        return $this->isDeersAuthenticated;
    }

    /**
     * Set deersAuthenticatedAt
     *
     * @param \DateTime $deersAuthenticatedAt
     * @return User
     */
    public function setDeersAuthenticatedAt($deersAuthenticatedAt) {
        $this->deersAuthenticatedAt = $deersAuthenticatedAt;

        return $this;
    }

    /**
     * Get deersAuthenticatedAt
     *
     * @return \DateTime 
     */
    public function getDeersAuthenticatedAt() {
        return $this->deersAuthenticatedAt;
    }

    /**
     * Set country
     *
     * @param \Lost\UserBundle\Entity\Country $country
     * @return User
     */
    public function setCountry(\Lost\UserBundle\Entity\Country $country = null) {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Lost\UserBundle\Entity\Country 
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Add userServices
     *
     * @param \Lost\UserBundle\Entity\UserService $userServices
     * @return User
     */
    public function addUserService(\Lost\UserBundle\Entity\UserService $userServices) {
        $this->userServices[] = $userServices;

        return $this;
    }

    /**
     * Remove userServices
     *
     * @param \Lost\UserBundle\Entity\UserService $userServices
     */
    public function removeUserService(\Lost\UserBundle\Entity\UserService $userServices) {
        $this->userServices->removeElement($userServices);
    }

    /**
     * Get userServices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserServices() {
        return $this->userServices;
    }

    /**
     * Set isIptvDisabled
     *
     * @param boolean $isIptvDisabled
     * @return User
     */
    public function setIsIptvDisabled($isIptvDisabled) {
        $this->isIptvDisabled = $isIptvDisabled;

        return $this;
    }

    /**
     * Get isIptvDisabled
     *
     * @return boolean 
     */
    public function getIsIptvDisabled() {
        return $this->isIptvDisabled;
    }

    /**
     * Set newSelevisionUser
     *
     * @param boolean $newSelevisionUser
     * @return User
     */
    public function setNewSelevisionUser($newSelevisionUser) {
        $this->newSelevisionUser = $newSelevisionUser;

        return $this;
    }

    /**
     * Get newSelevisionUser
     *
     * @return boolean 
     */
    public function getNewSelevisionUser() {
        return $this->newSelevisionUser;
    }

    /**
     * Add serviceSettings
     *
     * @param \Lost\UserBundle\Entity\UserServiceSetting $serviceSettings
     * @return User
     */
    public function addServiceSetting(\Lost\UserBundle\Entity\UserServiceSetting $serviceSettings) {
        $this->serviceSettings[] = $serviceSettings;

        return $this;
    }

    /**
     * Remove serviceSettings
     *
     * @param \Lost\UserBundle\Entity\UserServiceSetting $serviceSettings
     */
    public function removeServiceSetting(\Lost\UserBundle\Entity\UserServiceSetting $serviceSettings) {
        $this->serviceSettings->removeElement($serviceSettings);
    }

    /**
     * Get serviceSettings
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceSettings() {
        return $this->serviceSettings;
    }

    public function getName() {
        $name = '';
        if ($this->firstname) {
            $name .= $this->firstname;
            if ($this->lastname) {
                $name .= ' ';
            }
        } if ($this->lastname) {
            $name .= $this->lastname;
        } if ($name == '') {
            return $this->username;
        } return $name;
    }
    
    /*
     * function to get user role (single)
     */

    public function getSingleRole() {
        return $this->roles[0];
    }
    
    /**
     * Add groups
     *
     * @param \Lost\UserBundle\Entity\Group $groups
     * @return User
     */
    public function addGroup(\FOS\UserBundle\Model\GroupInterface $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Lost\UserBundle\Entity\Group $groups
     */
    public function removeGroup(\FOS\UserBundle\Model\GroupInterface $groups)
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
    
    public function getGroupObject() {
         
        // return first group object, there will be always single group.
        return $this->groups[0];
    }
    
    public function getGroup() {
        // return first group name, there will be always single group.
    	if(count($this->groups))
    	   return $this->groups[0]->getName();
    	else
    	    return null;
    }
    
    public function getGroupId() {
         
        // return first group name, there will be always single group.
        return $this->groups[0]->getId();
    }
    

    /**
     * Add userSetting
     *
     * @param \Lost\UserBundle\Entity\UserSetting $userSetting
     * @return User
     */
    public function addUserSetting(\Lost\UserBundle\Entity\UserSetting $userSetting)
    {
        $this->userSetting[] = $userSetting;

        return $this;
    }

    /**
     * Remove userSetting
     *
     * @param \Lost\UserBundle\Entity\UserSetting $userSetting
     */
    public function removeUserSetting(\Lost\UserBundle\Entity\UserSetting $userSetting)
    {
        $this->userSetting->removeElement($userSetting);
    }

    /**
     * Get userSetting
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserSetting()
    {
        return $this->userSetting;
    }

    /**
     * Set userSetting
     *
     * @param \Lost\UserBundle\Entity\UserSetting $userSetting
     * @return User
     */
    public function setUserSetting(\Lost\UserBundle\Entity\UserSetting $userSetting = null)
    {
        $this->userSetting[] = $userSetting;

        return $this;
    }

    /**
     * Set isLastLogin
     *
     * @param boolean $isLastLogin
     * @return User
     */
    public function setIsLastLogin($isLastLogin)
    {
        $this->isLastLogin = $isLastLogin;

        return $this;
    }

    /**
     * Get isLastLogin
     *
     * @return boolean 
     */
    public function getIsLastLogin()
    {
        return $this->isLastLogin;
    }

    /**
     * Add adminServiceLocation
     *
     * @param \Lost\AdminBundle\Entity\ServiceLocation $adminServiceLocation
     * @return User
     */
    public function addAdminServiceLocation(\Lost\AdminBundle\Entity\ServiceLocation $adminServiceLocation)
    {
        $this->adminServiceLocation[] = $adminServiceLocation;

        return $this;
    }

    /**
     * Remove adminServiceLocation
     *
     * @param \Lost\AdminBundle\Entity\ServiceLocation $adminServiceLocation
     */
    public function removeAdminServiceLocation(\Lost\AdminBundle\Entity\ServiceLocation $adminServiceLocation)
    {
        $this->adminServiceLocation->removeElement($adminServiceLocation);
    }

    /**
     * Get adminServiceLocation
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAdminServiceLocation()
    {
        return $this->adminServiceLocation;
    }

    /**
     * Add serviceLocations
     *
     * @param \Lost\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return User
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


    /**
     * Set ipAddressLong
     *
     * @param integer $ipAddressLong
     * @return User
     */
    public function setIpAddressLong($ipAddressLong)
    {
        $this->ipAddressLong = $ipAddressLong;

        return $this;
    }

    /**
     * Get ipAddressLong
     *
     * @return integer 
     */
    public function getIpAddressLong()
    {
        return $this->ipAddressLong;
    }

    /**
     * Add userMacAddress
     *
     * @param \Lost\UserBundle\Entity\UserMacAddress $userMacAddress
     * @return User
     */
    public function addUserMacAddress(\Lost\UserBundle\Entity\UserMacAddress $userMacAddress)
    {
        $this->userMacAddress[] = $userMacAddress;

        return $this;
    }

    /**
     * Remove userMacAddress
     *
     * @param \Lost\UserBundle\Entity\UserMacAddress $userMacAddress
     */
    public function removeUserMacAddress(\Lost\UserBundle\Entity\UserMacAddress $userMacAddress)
    {
        $this->userMacAddress->removeElement($userMacAddress);
    }

    /**
     * Get userMacAddress
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserMacAddress()
    {
        return $this->userMacAddress;
    }

    /**
     * Set cid
     *
     * @param integer $cid
     * @return User
     */
    public function setCid($cid)
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * Get cid
     *
     * @return integer 
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Add compensations
     *
     * @param \Lost\UserBundle\Entity\Compensation $compensations
     * @return User
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

    /**
     * Set userCredit
     *
     * @param \Lost\UserBundle\Entity\UserCredit $userCredit
     * @return User
     */
    public function setUserCredit(\Lost\UserBundle\Entity\UserCredit $userCredit = null)
    {
        $this->userCredit = $userCredit;

        return $this;
    }

    /**
     * Get userCredit
     *
     * @return \Lost\UserBundle\Entity\UserCredit 
     */
    public function getUserCredit()
    {
        return $this->userCredit;
    }
}
