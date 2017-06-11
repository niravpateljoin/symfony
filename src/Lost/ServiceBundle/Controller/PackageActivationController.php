<?php

namespace Lost\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Lost\ServiceBundle\Entity\ServiceActivationFailure;
use Lost\UserBundle\Entity\UserCreditLog;
use Lost\UserBundle\Entity\UserCredit;

class PackageActivationController extends Controller
{
    protected $container;
    
    protected $em;
    protected $session;
    protected $securitycontext;
    protected $paymentProcess;
    
    public function __construct($container) {
        
        $this->container = $container;
        
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->paymentProcess    = $container->get('paymentProcess');
        $this->UserLocationWiseService = $container->get('UserLocationWiseService');
    }
    

    public function activateServicePack()
    {
        $em                  = $this->em; //Init Entity Manager
        $user                = $this->securitycontext->getToken()->getUser();
        $purchaseOrderId     = $this->get('session')->get('PurchaseOrderId');
        $userServiceLocation = $this->UserLocationWiseService->getUserLocationService();
        
        $isISPForRefund    = false;
        $isIPTVForRefund   = false;
        $isAddOnForRefund  = false;
        
        $checkISPForIPTV   = false;
        $isISPActivated    = false;
        $isIPTVActivated   = false;
        
        if(array_key_exists('IPTV',$userServiceLocation) && array_key_exists('ISP',$userServiceLocation)){
            
            $checkISPForIPTV = true;
        }
                
        if(array_key_exists('ISP',$userServiceLocation)){
        
            $serviceISP = $this->em->getRepository('LostUserBundle:Service')->findOneByName('ISP');
            $isISPForRefund = $this->activationProcess($serviceISP);                       
        }
        
        if(array_key_exists('IPTV',$userServiceLocation)){
            
            $serviceIPTV = $this->em->getRepository('LostUserBundle:Service')->findOneByName('IPTV');
            
            //Active IPTV service
            if($checkISPForIPTV){
                
                $isISPActivated = $this->em->getRepository('LostUserBundle:UserService')->findBy(array('user' => $user, 'service' => $serviceISP->getId(), 'status' => '1', 'isAddon' => 0));
                if($isISPActivated){
                
                    $isIPTVForRefund = $this->activationProcess($serviceIPTV);
                }else{
                    
                    $isIPTVForRefund = $this->activationProcess($serviceIPTV,true);
                }
                
            }else{
                
                if(!array_key_exists('ISP',$userServiceLocation)){
                    
                    $isIPTVForRefund = $this->activationProcess($serviceIPTV);                    
                }                
            }
            
            //Active IPTV AddOn Package
            $isIPTVActivated = $this->em->getRepository('LostUserBundle:UserService')->findBy(array('user' => $user, 'service' => $serviceIPTV->getId(), 'status' => '1', 'isAddon' => 0));
            
            if($isIPTVActivated){

                $isAddOnForRefund = $this->activationProcess($serviceIPTV,false,'AddOn');
            }else{
                
                $isAddOnForRefund = $this->activationProcess($serviceIPTV,true,'AddOn');
            }
            
        }
        
        if($isISPForRefund || $isIPTVForRefund || $isAddOnForRefund){
            return true;
        }
        
        return false;
    }
    
    public function activationProcess($service,$defaultRefund = false,$type = ''){
        
        $user                = $this->securitycontext->getToken()->getUser();
        $purchaseOrderId     = $this->get('session')->get('PurchaseOrderId');
        $paymentRefundStatus = false;
        $isAddonPackage = 0;
        
        if($type == 'AddOn'){
            
            $isAddonPackage = 1;
        }
        
        if($service){
            
            //Get Service Activation Data
            $servicePurchases = $this->em->getRepository('LostServiceBundle:ServicePurchase')->getPaymentCompletedData($user,$purchaseOrderId,$service->getId(),$isAddonPackage);
            
            
            if($servicePurchases){
               
                foreach ($servicePurchases as $servicePurchase){
            
                    if($servicePurchase->getIsCredit() == 0){
                        
                        $activationStatus = false;
                        $serviceName = strtoupper($servicePurchase->getService()->getName());
                    
                        if(!$defaultRefund){
                            
                            //IPTV package activation process
                            if($serviceName == 'IPTV'){
                    
                                $activationStatus = $this->iptvProcess($servicePurchase);
                    
                                if($activationStatus){
                    
                                    $this->unsetActiveIPTVPackage();
                                }
                            }
                    
                            //ISP package activation process
                            if($serviceName == 'ISP'){
                    
                                $activationStatus = true;
                    
                                if($activationStatus){
                                    
                                    $this->unsetActiveISPPackage();
                                }
                            }
                        }
                    
                        if ($activationStatus){
                
                            $rechargeStatus = 1;
                        }else{
                            $rechargeStatus = 2;
                            $servicePurchase->setPaymentStatus('NeedToRefund');
                            $paymentRefundStatus = true;
                        }
                
                        //Update Service Purchase
                        $servicePurchase->setRechargeStatus($rechargeStatus);
                
                        $this->em->persist($servicePurchase);
                        $this->em->flush();
                        
                        if($rechargeStatus == 1){
                            
                            $this->paymentProcess->storeActiveUserService($servicePurchase);
                        }
                    }
                }
                
                return $paymentRefundStatus;
            }            
        }
        
        return false;
    }
    
    public function iptvProcess($service){
        
        $em      = $this->em; //Init Entity Manager
        $user    = $this->securitycontext->getToken()->getUser();
        $newUser = 0;
        
        // check selevision api to check whether customer exist in system
        $wsParam = array();
        $wsParam['cuLogin'] = $user->getUsername();
        
        $selevisionService = $this->get('selevisionService');
        $userExist = $selevisionService->checkUserExistInSelevision();
        
        if($userExist){
            
            //Call Selevision webservice for set package
            $wsOfferParam = array();
            $wsOfferParam['cuLogin']  = $user->getUserName();
            $wsOfferParam['offer']    = $service->getPackageId();
            
            $selevisionService = $this->get('selevisionService');
            $wsRes = $selevisionService->callWSAction('setCustomerOffer',$wsOfferParam);
            
            if($wsRes['status'] == 1){
            
                return true;
            }else{
            
                $error = (isset($wsRes['detail']))?$wsRes['detail']:$service->getPackageName().' faild to activate.';
                $this->packageActivationFailure($error,$service);
            }
        }
        return false;
    }
    
    public function unsetActiveIPTVPackage(){
        
        $em      = $this->em; //Init Entity Manager
        $user    = $this->securitycontext->getToken()->getUser();
        
        $service = $em->getRepository('LostUserBundle:Service')->findOneByName('IPTV');
        
        $condition = array('user' => $user, 'status' => 1, 'service' => $service);
        $objUserService = $em->getRepository('LostUserBundle:UserService')->findBy($condition);
        
        if($objUserService){
            
            foreach ($objUserService as $activeService){
                
                if($activeService->getIsAddon() == 0){

                    $packageId = $activeService->getPackageId();
                    
                    //Call Selevision webservice for unset package
                    $wsOfferParam = array();
                    $wsOfferParam['cuLogin']  = $user->getUserName();
                    $wsOfferParam['offer']    = $packageId;
                    
                    $selevisionService = $this->get('selevisionService');
                    $wsResponse = $selevisionService->callWSAction('unsetCustomerOffer',$wsOfferParam);
                    
                    if($wsResponse['status'] == 1){
                    
                        $activeService->setStatus(0);
                        $em->persist($activeService);
                        $em->flush();
                    }
                }
            }
        }        
    }
    
    public function unsetActiveISPPackage(){
    
        $em      = $this->em; //Init Entity Manager
        $user    = $this->securitycontext->getToken()->getUser();
    
        $service = $em->getRepository('LostUserBundle:Service')->findOneByName('ISP');
    
        $condition = array('user' => $user, 'status' => 1, 'service' => $service);
        $objUserService = $em->getRepository('LostUserBundle:UserService')->findBy($condition);
    
        if($objUserService){
    
            foreach ($objUserService as $activeService){
    
                $packageId = $activeService->getPackageId();
    
                $activeService->setStatus(0);
                $em->persist($activeService);
                $em->flush();                
            }
        }
    }

    public function packageActivationFailure($error,$servicePurchase) {

        $em     = $this->em; //Init Entity Manager
        $user   = $this->securitycontext->getToken()->getUser();

        //insert error to failure table
        $packageActivationFailure = new ServiceActivationFailure();

        $packageActivationFailure->setUser($user);
        $packageActivationFailure->setServices($servicePurchase->getService());
        $packageActivationFailure->setServicePurchases($servicePurchase);
        $packageActivationFailure->setPackageId($servicePurchase->getPackageId());
        $packageActivationFailure->setPackageName($servicePurchase->getPackageName());
        $packageActivationFailure->setFailureDescription($error);
        
        $em->persist($packageActivationFailure);
        $em->flush();
        
        $insertIdFailure = $packageActivationFailure->getId();        
    }
    
    public function addCreditInUserAccount(){
        
        $em     = $this->em; //Init Entity Manager
        $user   = $this->securitycontext->getToken()->getUser();
        $purchaseOrderId     = $this->get('session')->get('PurchaseOrderId');
    
        //Get Service Activation Data
        $servicePurchases = $em->getRepository('LostServiceBundle:ServicePurchase')->getPaymentCompletedData($user,$purchaseOrderId);

        $isRefundPayment = false;
        $rechargeStatus = 0;
        if($servicePurchases){
               
            foreach ($servicePurchases as $servicePurchase){
                
                if($servicePurchase->getIsCredit() == 1){
                    
                    $credit = $servicePurchase->getCredit()->getCredit();
                    $amount = $servicePurchase->getPayableAmount();
                    
                    $objUserCreditLog = new UserCreditLog();
                    
                    $objUserCreditLog->setUser($user);
                    $objUserCreditLog->setPurchaseOrder($servicePurchase->getPurchaseOrder());
                    $objUserCreditLog->setCredit($credit);
                    $objUserCreditLog->setAmount($amount);
                    $objUserCreditLog->setTransactionType('Credit');
                    
                    $em->persist($objUserCreditLog);
                    $em->flush();
                    
                    if($objUserCreditLog->getId()){
                        
                        $objUserCredit = $user->getUserCredit();
                        
                        if(!$objUserCredit){
                                                        
                            $objUserCredit = new UserCredit(); 
                        }else{                            
                            $credit = $credit + $objUserCredit->getTotalCredits();
                        }      

                        $objUserCredit->setUser($user);
                        $objUserCredit->setTotalCredits($credit);
                        $em->persist($objUserCredit);
                        $em->flush();
                        
                        if($objUserCredit->getId()){
                            
                            $rechargeStatus = 1;
                        }else{
                            
                            $isRefundPayment = true;
                            $servicePurchase->setPaymentStatus('NeedToRefund');
                        }
                    }
                    
                    $servicePurchase->setRechargeStatus($rechargeStatus);
                    $em->persist($servicePurchase);
                    $em->flush();
                }
            }
        }
        
        return $isRefundPayment;
    }        
}
