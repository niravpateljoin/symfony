<?php

namespace Lost\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Lost\UserBundle\Entity\User;
/**
 * ServiceLocation
 *
 * @ORM\Table(name="user_credit_log")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Lost\UserBundle\Repository\UserCreditLogRepository")
 */
class UserCreditLog
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userCreditLogs")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @ORM\ManyToOne(targetEntity="Lost\ServiceBundle\Entity\PurchaseOrder", inversedBy="userCreditLogs")
     * @ORM\JoinColumn(name="purchase_order_id", referencedColumnName="id")
     */
    protected $purchaseOrder;
    
    /**
     * @ORM\Column(name="credit", type="integer", nullable=false)
     */
    protected $credit;
    
    /**
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    protected $amount;
    
    /**
     * @ORM\Column(name="transaction_type", type="string", columnDefinition="ENUM('Credit', 'Debit')", options={"default":"Credit", "comment":"Credit,Debit"})
     */
    protected $transactionType;
    
    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;    
    
        
        

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
     * Set credit
     *
     * @param integer $credit
     * @return UserCreditLog
     */
    public function setCredit($credit)
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * Get credit
     *
     * @return integer 
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return UserCreditLog
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set transactionType
     *
     * @param string $transactionType
     * @return UserCreditLog
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    /**
     * Get transactionType
     *
     * @return string 
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserCreditLog
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
     * Set user
     *
     * @param \Lost\UserBundle\Entity\User $user
     * @return UserCreditLog
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
     * @return UserCreditLog
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
