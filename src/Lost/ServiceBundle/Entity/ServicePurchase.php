<?php

namespace Lost\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Lost\UserBundle\Entity\User;
use Lost\UserBundle\Entity\Service;


/**
 * @ORM\Entity
 * @ORM\Table(name="service_purchase")
 * @ORM\Entity(repositoryClass="Lost\ServiceBundle\Repository\ServicePurchaseRepository")
 */

class ServicePurchase {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Lost\UserBundle\Entity\Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Lost\UserBundle\Entity\User", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="PurchaseOrder", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="purchase_order_id", referencedColumnName="id")
     */
    protected $purchaseOrder;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Lost\AdminBundle\Entity\Credit", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="credit_id", referencedColumnName="id")
     */
    protected $credit;

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
     * @ORM\Column(name="payment_status", type="string", columnDefinition="ENUM('New', 'Completed', 'NeedToRefund', 'Refunded', 'Failed')", options={"default":"New", "comment":"New, Completed, NeedToRefund, Refunded, Failed"})
     */
    protected $paymentStatus = 'New';

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
     * @ORM\Column(name="payable_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $payableAmount;
    
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
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=255)
     */
    protected $sessionId;
    
    /**
     * @var smallint
     *
     * @ORM\Column(name="recharge_status", type="smallint", length=1, options={"comment":"0 => New, 1 => Success, 2 => Failed"})
     */
    protected $rechargeStatus = 0;
            
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
     * @var boolean
     *
     * @ORM\Column(name="is_upgrade", type="boolean", nullable=false)
     */
    protected $isUpgrade = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="terms_use", type="boolean", nullable=false)
     */
    protected $termsUse = false;
    
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
     *
     * @ORM\ManyToOne(targetEntity="PaymentMethod", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $paymentMethod;
        
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_addon", type="boolean", nullable=false)
     */
    protected $isAddon = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_credit", type="boolean", nullable=false)
     */
    protected $isCredit = false;
    
    /**
     * @ORM\OneToOne(targetEntity="Lost\UserBundle\Entity\UserService" , mappedBy="servicePurchase")
     */
    protected $userService;
            
    
    public function getActivationStatus(){
    
        if($this->rechargeStatus == 1){
    
            return 'Success';
        }
        
        if($this->rechargeStatus == 2){
        
            return 'Failed';
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
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set paymentStatus
     *
     * @param string $paymentStatus
     * @return ServicePurchase
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return string 
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Set actualAmount
     *
     * @param string $actualAmount
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set payableAmount
     *
     * @param string $payableAmount
     * @return ServicePurchase
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
     * Set unusedCredit
     *
     * @param string $unusedCredit
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set sessionId
     *
     * @param string $sessionId
     * @return ServicePurchase
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return string 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set rechargeStatus
     *
     * @param integer $rechargeStatus
     * @return ServicePurchase
     */
    public function setRechargeStatus($rechargeStatus)
    {
        $this->rechargeStatus = $rechargeStatus;

        return $this;
    }

    /**
     * Get rechargeStatus
     *
     * @return integer 
     */
    public function getRechargeStatus()
    {
        return $this->rechargeStatus;
    }

    /**
     * Set bandwidth
     *
     * @param integer $bandwidth
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set isUpgrade
     *
     * @param boolean $isUpgrade
     * @return ServicePurchase
     */
    public function setIsUpgrade($isUpgrade)
    {
        $this->isUpgrade = $isUpgrade;

        return $this;
    }

    /**
     * Get isUpgrade
     *
     * @return boolean 
     */
    public function getIsUpgrade()
    {
        return $this->isUpgrade;
    }

    /**
     * Set termsUse
     *
     * @param boolean $termsUse
     * @return ServicePurchase
     */
    public function setTermsUse($termsUse)
    {
        $this->termsUse = $termsUse;

        return $this;
    }

    /**
     * Get termsUse
     *
     * @return boolean 
     */
    public function getTermsUse()
    {
        return $this->termsUse;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set isAddon
     *
     * @param boolean $isAddon
     * @return ServicePurchase
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
     * Set isCredit
     *
     * @param boolean $isCredit
     * @return ServicePurchase
     */
    public function setIsCredit($isCredit)
    {
        $this->isCredit = $isCredit;

        return $this;
    }

    /**
     * Get isCredit
     *
     * @return boolean 
     */
    public function getIsCredit()
    {
        return $this->isCredit;
    }

    /**
     * Set service
     *
     * @param \Lost\UserBundle\Entity\Service $service
     * @return ServicePurchase
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
     * Set user
     *
     * @param \Lost\UserBundle\Entity\User $user
     * @return ServicePurchase
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
     * Set purchaseOrder
     *
     * @param \Lost\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return ServicePurchase
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

    /**
     * Set credit
     *
     * @param \Lost\AdminBundle\Entity\Credit $credit
     * @return ServicePurchase
     */
    public function setCredit(\Lost\AdminBundle\Entity\Credit $credit = null)
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * Get credit
     *
     * @return \Lost\AdminBundle\Entity\Credit 
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * Set paymentMethod
     *
     * @param \Lost\ServiceBundle\Entity\PaymentMethod $paymentMethod
     * @return ServicePurchase
     */
    public function setPaymentMethod(\Lost\ServiceBundle\Entity\PaymentMethod $paymentMethod = null)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return \Lost\ServiceBundle\Entity\PaymentMethod 
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set userService
     *
     * @param \Lost\UserBundle\Entity\UserService $userService
     * @return ServicePurchase
     */
    public function setUserService(\Lost\UserBundle\Entity\UserService $userService = null)
    {
        $this->userService = $userService;

        return $this;
    }

    /**
     * Get userService
     *
     * @return \Lost\UserBundle\Entity\UserService 
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * Set userCreditLog
     *
     * @param \Lost\UserBundle\Entity\UserCreditLog $userCreditLog
     * @return ServicePurchase
     */
    public function setUserCreditLog(\Lost\UserBundle\Entity\UserCreditLog $userCreditLog = null)
    {
        $this->userCreditLog = $userCreditLog;

        return $this;
    }

    /**
     * Get userCreditLog
     *
     * @return \Lost\UserBundle\Entity\UserCreditLog 
     */
    public function getUserCreditLog()
    {
        return $this->userCreditLog;
    }
}
