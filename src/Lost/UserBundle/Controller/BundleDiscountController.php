<?php

namespace Lost\UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use FOS\UserBundle\Mailer\MailerInterface;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\Service;

class BundleDiscountController extends Controller 
{

    protected $container;
    
    protected $em;
    protected $session;
    protected $securitycontext;
    protected $request;
    
    protected $DashboardSummary;
    
    public function __construct($container) {
    
        $this->container = $container;
    
        $this->GeoLocation       = $container->get('GeoLocation');
        $this->UserLocationWiseService       = $container->get('UserLocationWiseService');
        $this->DashboardSummary       = $container->get('DashboardSummary');
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->request           = $container->get('request');
    }
    
    public function getBundleDiscount() {

        $clientDetails  = $this->get('GeoLocation')->getIPAddress('all');
        $clientIp       = $clientDetails['ip'];
        $user           = $this->securitycontext->getToken()->getUser();
        $sessionId      = $this->session->get('sessionId');
        
        if($clientDetails){
        
            //Get Country
            $country = $this->em->getRepository('LostUserBundle:Country')->findOneBy(array('isoCode' => $clientDetails['country_code']));
            
            $summaryCartData = $this->DashboardSummary->getUserServiceSummary();
            
            // need to remove. not being used anywhere
            $LocationService = $this->UserLocationWiseService->getUserLocationService();
            
            $bundleDiscountAvailable = 0;
            $discountArr = array();
            $totalISPAmount = 0;
            $ispPackAdded   = 0;
            if(in_array('IPTV',$summaryCartData['AvailableServicesOnLocation']) && in_array('ISP',$summaryCartData['AvailableServicesOnLocation'])) {
    
                $cartData = $summaryCartData['Cart'];
                
                if($cartData){
                
                        
                    if(array_key_exists('ISP',$cartData)){
                
                        if(isset($cartData['ISP']['RegularPack']) && !empty($cartData['ISP']['RegularPack'])){
                
                            $ispPackAdded = 1;
                
                            foreach ($cartData['ISP']['RegularPack'] as $ispPackage){
                
                                $totalISPAmount += $ispPackage['amount'];
                            }                                                
                        }
                
                    }
                }
                
    
                ########## START CODE FOR SERVICE LOCATION WISE DISCOUNT ##########
    
                $serviceLocationDiscount = $this->em->getRepository('LostAdminBundle:ServiceLocation')->getUserLocationWiseDiscount(ip2long($clientIp),$country->getId());
                
                if(!empty($serviceLocationDiscount)) {
                    
                    foreach ($serviceLocationDiscount->getServiceLocationDiscounts() as $discount) {
                        
                        $minAmt = $discount->getMinAmount();
                        $maxAmt = $discount->getMaxAmount();
                        $percentage = $discount->getPercentage();
                        
                        if($totalISPAmount >= $minAmt && $totalISPAmount <= $maxAmt) {
                    
                            $discountArr['Precentage'] = $percentage;
                        }
                    }
                    
                }
    
                ##########  END  CODE FOR SERVICE LOCATION WISE DISCOUNT ##########
                
                
                ########## START CODE FOR COUNTRY WISE DISCOUNT ##########
                if(empty($discountArr)) {
                    
                    
                    
                    $countryDiscounts = $this->em->getRepository('LostAdminBundle:GlobalDiscount')->getAllDiscountCountry($country->getId());
                    
                    if($countryDiscounts) {
                        
                        foreach($countryDiscounts as $discount) {
                            
                            $minAmt = $discount->getMinAmount();
                            $maxAmt = $discount->getMaxAmount();
                            $percentage = $discount->getPercentage();
                            
                            if($totalISPAmount >= $minAmt && $totalISPAmount <= $maxAmt) {
                            
                                $discountArr['Precentage'] = $percentage;
                            }
                        }
                        
                    } else {
                        
                        $countryDiscounts = $this->em->getRepository('LostAdminBundle:GlobalDiscount')->getAllDiscountCountry(null);
                        
                        if($countryDiscounts) {
                        
                            foreach($countryDiscounts as $discount) {
                        
                                $minAmt = $discount->getMinAmount();
                                $maxAmt = $discount->getMaxAmount();
                                $percentage = $discount->getPercentage();
                        
                                if($totalISPAmount >= $minAmt && $totalISPAmount <= $maxAmt) {
                        
                                    $discountArr['Precentage'] = $percentage;
                                }
                            }
                            
                        }
                    }
                }
                ##########  END  CODE FOR COUNTRY WISE DISCOUNT ##########
                
                $discountPrecentage = NULL;
                
                if(isset($discountArr['Precentage']) && !empty($discountArr['Precentage'])) {
                    
                    $discountPrecentage = $discountArr['Precentage'];
                }
            
                //Apply discount on current cart IPTV package
                $userCartItems = $this->em->getRepository('LostServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId,'New');
            
                if($userCartItems){
            
                    foreach ($userCartItems as $item){
            
                        if(strtoupper($item->getService()->getName()) == 'IPTV' && $item->getIsAddon() == 0){
                            
                            $discountAmount = NULL;
                            
                            if($discountPrecentage){
                                
                                $discountAmount = ($item->getActualAmount() * $discountPrecentage) / 100;
                            }
                            
            
                            $item->setTotalDiscount($discountAmount);
                            $item->setDiscountRate($discountPrecentage);
                            $item->setPayableAmount($item->getPayableAmount() - $discountAmount);
                            $this->em->persist($item);    
                        }else{
                            
                            $item->setTotalDiscount(NULL);
                            $item->setDiscountRate(NULL);
                            $item->setPayableAmount($item->getPayableAmount() + $item->getTotalDiscount());
                            $this->em->persist($item);
                        }
                    }
                    $this->em->flush();
                }
                    
                if(!empty($discountArr)){
                    
                    return $discountArr;
                }        
            }
            
            return false;
        }       
    }
}
