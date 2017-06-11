<?php

namespace Lost\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="payment_method")
 * @ORM\Entity(repositoryClass="Lost\ServiceBundle\Repository\PaymentMethodRepository")
 */

class PaymentMethod {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $name;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $code;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated_at;
        

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
     * @return PaymentMethod
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return PaymentMethod
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return PaymentMethod
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paypalCheckout = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add paypalCheckout
     *
     * @param \Lost\ServiceBundle\Entity\PaypalCheckout $paypalCheckout
     * @return PaymentMethod
     */
    public function addPaypalCheckout(\Lost\ServiceBundle\Entity\PaypalCheckout $paypalCheckout)
    {
        $this->paypalCheckout[] = $paypalCheckout;

        return $this;
    }

    /**
     * Remove paypalCheckout
     *
     * @param \Lost\ServiceBundle\Entity\PaypalCheckout $paypalCheckout
     */
    public function removePaypalCheckout(\Lost\ServiceBundle\Entity\PaypalCheckout $paypalCheckout)
    {
        $this->paypalCheckout->removeElement($paypalCheckout);
    }

    /**
     * Get paypalCheckout
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPaypalCheckout()
    {
        return $this->paypalCheckout;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return PaymentMethod
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
}
