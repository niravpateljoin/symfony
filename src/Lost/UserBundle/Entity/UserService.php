<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Lost\UserBundle\Entity\User;
use Lost\UserBundle\Entity\Service;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_services")
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\UserServiceRepository")
 */
class UserService
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
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userServices")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="userServices")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;
    
    /**
     * @ORM\OneToOne(targetEntity="Lost\ServiceBundle\Entity\ServicePurchase", inversedBy="userService")
     * @ORM\JoinColumn(name="service_purchase_id", referencedColumnName="id")
     */
    protected $servicePurchase;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="package_id", length=255)
     */
    protected $packageId;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="package_name", length=255)
     */
    protected $packageName;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="actual_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $actualAmount;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="total_discount", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $totalDiscount;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="discount_rate", type="integer", nullable=true)
     */
    protected $discountRate;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="unused_credit", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $unusedCredit;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="unused_days", type="integer", nullable=true)
     */
    protected $unusedDays;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="payable_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $payableAmount;
    
    /**
     * @ORM\Column(name="activation_date", type="datetime", nullable=true)     
     */
    protected $activationDate;
    
    /**
     * @ORM\Column(name="expiry_date", type="datetime", nullable=true)
     */
    protected $expiryDate;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="sent_exp_notification", type="boolean", nullable=false)
     */
    protected $sentExpiredNotification = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_addon", type="boolean", nullable=false)
     */
    protected $isAddon = false;
        
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="service_location_ip", length=15)
     */
    protected $serviceLocationIp;
    
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", name="bandwidth", nullable=true)
     */
    protected $bandwidth;
    
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", name="validity", nullable=true)
     */
    protected $validity;
    
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
     * @var boolean
     *
     * @ORM\Column(name="refund", type="boolean", nullable=true)
     */
    protected $refund = false;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="refund_amount", type="decimal", precision= 10, scale= 2, options={"default":0}, nullable=true)
     */
    protected $refundAmount;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Lost\ServiceBundle\Entity\PurchaseOrder", inversedBy="userService")
     * @ORM\JoinColumn(name="purchase_order_id", referencedColumnName="id")
     */
    protected $purchaseOrder;
    
    
    public function getDisplayStatus(){
    
        if($this->status == 1){
    
            return 'Active';
        }else{
            return 'Expired';
        }    
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
     * Set packageId
     *
     * @param string $packageId
     * @return UserService
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return string 
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set packageName
     *
     * @param string $packageName
     * @return UserService
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;

        return $this;
    }

    /**
     * Get packageName
     *
     * @return string 
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * Set actualAmount
     *
     * @param string $actualAmount
     * @return UserService
     */
    public function setActualAmount($actualAmount)
    {
        $this->actualAmount = $actualAmount;

        return $this;
    }

    /**
     * Get actualAmount
     *
     * @return string 
     */
    public function getActualAmount()
    {
        return $this->actualAmount;
    }

    /**
     * Set totalDiscount
     *
     * @param string $totalDiscount
     * @return UserService
     */
    public function setTotalDiscount($totalDiscount)
    {
        $this->totalDiscount = $totalDiscount;

        return $this;
    }

    /**
     * Get totalDiscount
     *
     * @return string 
     */
    public function getTotalDiscount()
    {
        return $this->totalDiscount;
    }

    /**
     * Set discountRate
     *
     * @param integer $discountRate
     * @return UserService
     */
    public function setDiscountRate($discountRate)
    {
        $this->discountRate = $discountRate;

        return $this;
    }

    /**
     * Get discountRate
     *
     * @return integer 
     */
    public function getDiscountRate()
    {
        return $this->discountRate;
    }

    /**
     * Set unusedCredit
     *
     * @param string $unusedCredit
     * @return UserService
     */
    public function setUnusedCredit($unusedCredit)
    {
        $this->unusedCredit = $unusedCredit;

        return $this;
    }

    /**
     * Get unusedCredit
     *
     * @return string 
     */
    public function getUnusedCredit()
    {
        return $this->unusedCredit;
    }

    /**
     * Set unusedDays
     *
     * @param integer $unusedDays
     * @return UserService
     */
    public function setUnusedDays($unusedDays)
    {
        $this->unusedDays = $unusedDays;

        return $this;
    }

    /**
     * Get unusedDays
     *
     * @return integer 
     */
    public function getUnusedDays()
    {
        return $this->unusedDays;
    }

    /**
     * Set payableAmount
     *
     * @param string $payableAmount
     * @return UserService
     */
    public function setPayableAmount($payableAmount)
    {
        $this->payableAmount = $payableAmount;

        return $this;
    }

    /**
     * Get payableAmount
     *
     * @return string 
     */
    public function getPayableAmount()
    {
        return $this->payableAmount;
    }

    /**
     * Set activationDate
     *
     * @param \DateTime $activationDate
     * @return UserService
     */
    public function setActivationDate($activationDate)
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    /**
     * Get activationDate
     *
     * @return \DateTime 
     */
    public function getActivationDate()
    {
        return $this->activationDate;
    }

    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return UserService
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set sentExpiredNotification
     *
     * @param boolean $sentExpiredNotification
     * @return UserService
     */
    public function setSentExpiredNotification($sentExpiredNotification)
    {
        $this->sentExpiredNotification = $sentExpiredNotification;

        return $this;
    }

    /**
     * Get sentExpiredNotification
     *
     * @return boolean 
     */
    public function getSentExpiredNotification()
    {
        return $this->sentExpiredNotification;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return UserService
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
     * Set isAddon
     *
     * @param boolean $isAddon
     * @return UserService
     */
    public function setIsAddon($isAddon)
    {
        $this->isAddon = $isAddon;

        return $this;
    }

    /**
     * Get isAddon
     *
     * @return boolean 
     */
    public function getIsAddon()
    {
        return $this->isAddon;
    }

    /**
     * Set serviceLocationIp
     *
     * @param string $serviceLocationIp
     * @return UserService
     */
    public function setServiceLocationIp($serviceLocationIp)
    {
        $this->serviceLocationIp = $serviceLocationIp;

        return $this;
    }

    /**
     * Get serviceLocationIp
     *
     * @return string 
     */
    public function getServiceLocationIp()
    {
        return $this->serviceLocationIp;
    }

    /**
     * Set bandwidth
     *
     * @param integer $bandwidth
     * @return UserService
     */
    public function setBandwidth($bandwidth)
    {
        $this->bandwidth = $bandwidth;

        return $this;
    }

    /**
     * Get bandwidth
     *
     * @return integer 
     */
    public function getBandwidth()
    {
        return $this->bandwidth;
    }

    /**
     * Set validity
     *
     * @param integer $validity
     * @return UserService
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * Get validity
     *
     * @return integer 
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserService
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return UserService
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set refund
     *
     * @param boolean $refund
     * @return UserService
     */
    public function setRefund($refund)
    {
        $this->refund = $refund;

        return $this;
    }

    /**
     * Get refund
     *
     * @return boolean 
     */
    public function getRefund()
    {
        return $this->refund;
    }

    /**
     * Set refundAmount
     *
     * @param string $refundAmount
     * @return UserService
     */
    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;

        return $this;
    }

    /**
     * Get refundAmount
     *
     * @return string 
     */
    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    /**
     * Set user
     *
     * @param \Lost\UserBundle\Entity\User $user
     * @return UserService
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
     * Set service
     *
     * @param \Lost\UserBundle\Entity\Service $service
     * @return UserService
     */
    public function setService(\Lost\UserBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Lost\UserBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set servicePurchase
     *
     * @param \Lost\ServiceBundle\Entity\ServicePurchase $servicePurchase
     * @return UserService
     */
    public function setServicePurchase(\Lost\ServiceBundle\Entity\ServicePurchase $servicePurchase = null)
    {
        $this->servicePurchase = $servicePurchase;

        return $this;
    }

    /**
     * Get servicePurchase
     *
     * @return \Lost\ServiceBundle\Entity\ServicePurchase 
     */
    public function getServicePurchase()
    {
        return $this->servicePurchase;
    }

    /**
     * Set purchaseOrder
     *
     * @param \Lost\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return UserService
     */
    public function setPurchaseOrder(\Lost\ServiceBundle\Entity\PurchaseOrder $purchaseOrder = null)
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }

    /**
     * Get purchaseOrder
     *
     * @return \Lost\ServiceBundle\Entity\PurchaseOrder 
     */
    public function getPurchaseOrder()
    {
        return $this->purchaseOrder;
    }
}
