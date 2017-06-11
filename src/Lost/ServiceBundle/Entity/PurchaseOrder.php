<?php

namespace Lost\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Lost\UserBundle\Entity\User;
use Lost\UserBundle\Entity\Service;


/**
 * @ORM\Entity
 * @ORM\Table(name="purchase_order")
 * @ORM\Entity(repositoryClass="Lost\ServiceBundle\Repository\PurchaseOrderRepository")
 */

class PurchaseOrder {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Lost\UserBundle\Entity\User", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="order_number", length=255)
     */
    protected $orderNumber;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="PaymentMethod", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $paymentMethod;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="session_id", length=255)
     */
    protected $sessionId;
        
    /**
     * @var decimal
     *
     * @ORM\Column(name="total_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $totalAmount;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="refund_amount", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $refundAmount;        

    /**
     * @ORM\OneToMany(targetEntity="ServicePurchase", mappedBy="purchaseOrder")
     */
    protected $servicePurchases;
    
    /**
     * @ORM\OneToMany(targetEntity="Lost\UserBundle\Entity\UserService", mappedBy="purchaseOrder")
     */
    protected $userService;
    
    /**
     * @ORM\OneToMany(targetEntity="Lost\UserBundle\Entity\UserCreditLog", mappedBy="purchaseOrder")
     */
    protected $userCreditLogs;
        
    /**
     * @ORM\OneToOne(targetEntity="Milstar", inversedBy="purchaseOrder")
     * @ORM\JoinColumn(name="milstar_id", referencedColumnName="id")
     */
    protected $milstar;

    /**
     * @ORM\OneToOne(targetEntity="PaypalCheckout", inversedBy="purchaseOrder")
     * @ORM\JoinColumn(name="paypal_checkout_id", referencedColumnName="id")
     */
    protected $paypalCheckout;
    
    /**
     * @var string
     *
     * @ORM\Column(name="paypal_token", type="string", length=255, nullable=true)
     */
    protected $paypalToken;
    
    /**
     * @ORM\Column(name="payment_status", type="string", columnDefinition="ENUM('InProcess', 'Completed', 'PartiallyCompleted', 'Refunded', 'Failed')", options={"default":"InProcess", "comment":"InProcess, Completed, PartiallyCompleted, Refunded, Failed"})
     */
    protected $paymentStatus = 'InProcess';
    
    
    /**
     * @ORM\Column(name="payment_by", type="string", columnDefinition="ENUM('User', 'Admin')", options={"default":"User", "comment":"User, Admin"})
     */
    protected $paymentBy = 'User';
    
    /**
     * @ORM\OneToMany(targetEntity="Lost\UserBundle\Entity\User", mappedBy="purchaseOrder")
     * @ORM\JoinColumn(name="payment_by_user", referencedColumnName="id")
     */
    protected $paymentByUser;
    
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
     * @var integer
     *
     * @ORM\Column(type="integer", name="compensation_validity", length=11, nullable=true)
     */
    protected $compensationValidity;
    
                        
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->servicePurchases = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userService = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set orderNumber
     *
     * @param string $orderNumber
     * @return PurchaseOrder
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * Get orderNumber
     *
     * @return string 
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * Set sessionId
     *
     * @param string $sessionId
     * @return PurchaseOrder
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
     * Set totalAmount
     *
     * @param string $totalAmount
     * @return PurchaseOrder
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return string 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set refundAmount
     *
     * @param string $refundAmount
     * @return PurchaseOrder
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
     * Set paypalToken
     *
     * @param string $paypalToken
     * @return PurchaseOrder
     */
    public function setPaypalToken($paypalToken)
    {
        $this->paypalToken = $paypalToken;

        return $this;
    }

    /**
     * Get paypalToken
     *
     * @return string 
     */
    public function getPaypalToken()
    {
        return $this->paypalToken;
    }

    /**
     * Set paymentStatus
     *
     * @param string $paymentStatus
     * @return PurchaseOrder
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
     * Set paymentBy
     *
     * @param string $paymentBy
     * @return PurchaseOrder
     */
    public function setPaymentBy($paymentBy)
    {
        $this->paymentBy = $paymentBy;

        return $this;
    }

    /**
     * Get paymentBy
     *
     * @return string 
     */
    public function getPaymentBy()
    {
        return $this->paymentBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PurchaseOrder
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
     * @return PurchaseOrder
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
     * Set compensationValidity
     *
     * @param integer $compensationValidity
     * @return PurchaseOrder
     */
    public function setCompensationValidity($compensationValidity)
    {
        $this->compensationValidity = $compensationValidity;

        return $this;
    }

    /**
     * Get compensationValidity
     *
     * @return integer 
     */
    public function getCompensationValidity()
    {
        return $this->compensationValidity;
    }

    /**
     * Set user
     *
     * @param \Lost\UserBundle\Entity\User $user
     * @return PurchaseOrder
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
     * Set paymentMethod
     *
     * @param \Lost\ServiceBundle\Entity\PaymentMethod $paymentMethod
     * @return PurchaseOrder
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
     * Add servicePurchases
     *
     * @param \Lost\ServiceBundle\Entity\ServicePurchase $servicePurchases
     * @return PurchaseOrder
     */
    public function addServicePurchase(\Lost\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases[] = $servicePurchases;

        return $this;
    }

    /**
     * Remove servicePurchases
     *
     * @param \Lost\ServiceBundle\Entity\ServicePurchase $servicePurchases
     */
    public function removeServicePurchase(\Lost\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases->removeElement($servicePurchases);
    }

    /**
     * Get servicePurchases
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServicePurchases()
    {
        return $this->servicePurchases;
    }

    /**
     * Add userService
     *
     * @param \Lost\UserBundle\Entity\UserService $userService
     * @return PurchaseOrder
     */
    public function addUserService(\Lost\UserBundle\Entity\UserService $userService)
    {
        $this->userService[] = $userService;

        return $this;
    }

    /**
     * Remove userService
     *
     * @param \Lost\UserBundle\Entity\UserService $userService
     */
    public function removeUserService(\Lost\UserBundle\Entity\UserService $userService)
    {
        $this->userService->removeElement($userService);
    }

    /**
     * Get userService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * Add userCreditLogs
     *
     * @param \Lost\UserBundle\Entity\UserCreditLog $userCreditLogs
     * @return PurchaseOrder
     */
    public function addUserCreditLog(\Lost\UserBundle\Entity\UserCreditLog $userCreditLogs)
    {
        $this->userCreditLogs[] = $userCreditLogs;

        return $this;
    }

    /**
     * Remove userCreditLogs
     *
     * @param \Lost\UserBundle\Entity\UserCreditLog $userCreditLogs
     */
    public function removeUserCreditLog(\Lost\UserBundle\Entity\UserCreditLog $userCreditLogs)
    {
        $this->userCreditLogs->removeElement($userCreditLogs);
    }

    /**
     * Get userCreditLogs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserCreditLogs()
    {
        return $this->userCreditLogs;
    }

    /**
     * Set milstar
     *
     * @param \Lost\ServiceBundle\Entity\Milstar $milstar
     * @return PurchaseOrder
     */
    public function setMilstar(\Lost\ServiceBundle\Entity\Milstar $milstar = null)
    {
        $this->milstar = $milstar;

        return $this;
    }

    /**
     * Get milstar
     *
     * @return \Lost\ServiceBundle\Entity\Milstar 
     */
    public function getMilstar()
    {
        return $this->milstar;
    }

    /**
     * Set paypalCheckout
     *
     * @param \Lost\ServiceBundle\Entity\PaypalCheckout $paypalCheckout
     * @return PurchaseOrder
     */
    public function setPaypalCheckout(\Lost\ServiceBundle\Entity\PaypalCheckout $paypalCheckout = null)
    {
        $this->paypalCheckout = $paypalCheckout;

        return $this;
    }

    /**
     * Get paypalCheckout
     *
     * @return \Lost\ServiceBundle\Entity\PaypalCheckout 
     */
    public function getPaypalCheckout()
    {
        return $this->paypalCheckout;
    }

    /**
     * Add paymentByUser
     *
     * @param \Lost\UserBundle\Entity\User $paymentByUser
     * @return PurchaseOrder
     */
    public function addPaymentByUser(\Lost\UserBundle\Entity\User $paymentByUser)
    {
        $this->paymentByUser[] = $paymentByUser;

        return $this;
    }

    /**
     * Remove paymentByUser
     *
     * @param \Lost\UserBundle\Entity\User $paymentByUser
     */
    public function removePaymentByUser(\Lost\UserBundle\Entity\User $paymentByUser)
    {
        $this->paymentByUser->removeElement($paymentByUser);
    }

    /**
     * Get paymentByUser
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPaymentByUser()
    {
        return $this->paymentByUser;
    }
}
