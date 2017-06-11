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
use Lost\UserBundle\Form\Type\AccountFormType;
use Lost\UserBundle\Form\Type\ChangePasswordFormType;
use Lost\UserBundle\Form\Type\AccountSettingFormType;
use Lost\UserBundle\Form\Type\AccountTypeFormType;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\Service;
use Lost\ServiceBundle\Entity\ServicePurchase;

class DashboardSummaryController extends Controller {

    protected $container;
    
    protected $em;
    protected $session;
    protected $securitycontext;
    protected $request;
    protected $UserLocationWiseService;
    
    public function __construct($container) {
    
        $this->container = $container;
    
        $this->UserLocationWiseService       = $container->get('UserLocationWiseService');
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->request           = $container->get('request');
        $this->ActivityLog       = $container->get('ActivityLog');
        $this->optimizeLogger    = $container->get('optimizeLogger');
    }
    
    public function getUserServiceSummary() {
        
        $user      = $this->securitycontext->getToken()->getUser();
        $sessionId = $this->session->get('sessionId');
        
        $LocationService = $this->UserLocationWiseService->getUserLocationService();
        
        $accountSummary = array();
        
        $accountSummary['Cart']                         = '';
        $accountSummary['CartAvailable']                = 0;
        $accountSummary['CartIPTVPackageId']            = array();
        $accountSummary['CartAddOnPackageId']           = array();
        $accountSummary['CartISPPackageId']             = array();
        $accountSummary['IsISPAvailabledInCart']        = 0;
        $accountSummary['IsAddOnAvailabledInCart']      = 0;
        $accountSummary['IsIPTVAvailabledInCart']       = 0;
        $accountSummary['ServiceAvailableInCart']       = array();
        $accountSummary['TotalCartAmount']              = 0;
        $accountSummary['Purchased']                    = '';
        $accountSummary['PurchasedAvailable']           = 0;
        $accountSummary['PurchasedIPTVPackageId']       = array();
        $accountSummary['PurchasedAddOnPackageId']      = array();
        $accountSummary['PurchasedISPPackageId']        = array();
        $accountSummary['ServiceAvailableInPurchased']  = array();
        $accountSummary['IsISPAvailabledInPurchased']   = 0;
        $accountSummary['IsAddOnAvailabledInPurchased'] = 0;
        $accountSummary['IsIPTVAvailabledInPurchased']  = 0;
        $accountSummary['TotalPurchasedAmount']         = 0;
        $accountSummary['AvailableServicesOnLocation']  = array();
        $accountSummary['IPTVButtonEnabled']            = 1;    
        $accountSummary['IsCreditAvailabledInCart']     = 0;
        
        
        if(array_key_exists('IPTV',$LocationService) && array_key_exists('ISP',$LocationService)){
            
            $accountSummary['IPTVButtonEnabled']  = 0;
        }
        
        if($LocationService){

            foreach ($LocationService as $key => $val){
                
                if($key == 'IPTV' || $key == 'ISP'){
                    
                    $accountSummary['AvailableServicesOnLocation'][] = strtoupper($val['ServiceName']);
                }
            }            
        }
        
        //Cart Purchase data
        $cartPurchaseData = $this->em->getRepository('LostServiceBundle:ServicePurchase')->getCartPurchaseItem($user,$sessionId);        
        if($cartPurchaseData){
            
            $accountSummary['CartAvailable'] = 1;
            
            foreach ($cartPurchaseData as $cartPurchase){
                
                if($cartPurchase->getService() && $cartPurchase->getIsCredit() != 1){
                    if(in_array($cartPurchase->getService()->getName(),$accountSummary['AvailableServicesOnLocation'])){
                        
                        $totalCartAmount = 0;
                        
                        $serviceId          = $cartPurchase->getService()->getId();
                        $serviceName        = $cartPurchase->getService()->getName();
                        $isAddOn            = $cartPurchase->getIsAddon();
                        $packageName        = $cartPurchase->getPackageName();
                        $packageId          = $cartPurchase->getPackageId();
                        $amount             = $cartPurchase->getActualAmount();
                        $payableAmount      = $cartPurchase->getPayableAmount();
                        $discountPercentage = $cartPurchase->getDiscountRate();
                        $discountAmount     = $cartPurchase->getTotalDiscount();
                    
                        $tempArr = array();
                        $tempArr['serviceName']         = $serviceName;
                        $tempArr['serviceId']           = $serviceId;
                        $tempArr['packageName']         = $packageName;
                        $tempArr['packageId']           = $packageId;
                        $tempArr['amount']              = $amount;
                        $tempArr['payableAmount']       = $payableAmount;
                        $tempArr['discountPercentage']  = $discountPercentage;
                        $tempArr['discountAmount']      = $discountAmount;
                        $tempArr['servicePurchaseId']   = $cartPurchase->getId();
                        $tempArr['validity']            = $cartPurchase->getValidity();
                        
                        if(strtoupper($serviceName) == 'ISP'){
                            $accountSummary['IPTVButtonEnabled'] = 1;
                        }                
                        
                        array_push($accountSummary['ServiceAvailableInCart'],strtoupper($serviceName));
                        
                        if($isAddOn){
                            
                            $totalCartAmount = $totalCartAmount + $payableAmount;
                        
                            $accountSummary['CartAddOnPackageId'][] = $packageId;
                            $accountSummary['IsAddOnAvailabledInCart'] = 1;
                            $accountSummary['Cart'][strtoupper($serviceName)]['AddOnPack'][] = $tempArr;
                        }else{
                            
                            if($discountPercentage){
                                $amount = $amount - $discountAmount;
                            }
                            $totalCartAmount = $totalCartAmount + $amount;
                            $totalCartAmount -= $cartPurchase->getUnusedCredit();
                            
                            $accountSummary['Is'.strtoupper($serviceName).'AvailabledInCart'] = 1;
                            $accountSummary['Cart'.strtoupper($serviceName).'PackageId'][] = $packageId; 
                            $accountSummary['Cart'][strtoupper($serviceName)]['RegularPack'][] = $tempArr;     
                            
                            $accountSummary['Cart'][strtoupper($serviceName)]['Current'.strtoupper($serviceName).'Packvalidity']  = $cartPurchase->getValidity();
                            $accountSummary['Cart'][strtoupper($serviceName)]['unusedCredit'] = $cartPurchase->getUnusedCredit();
                            $accountSummary['Cart'][strtoupper($serviceName)]['unusedDays']   = $cartPurchase->getUnusedDays();
                                                
                        }
                        
                        $accountSummary['TotalCartAmount'] += $totalCartAmount;
                    }   
                }
                
                if($cartPurchase->getIsCredit() == 1){
                     
                    if($cartPurchase->getCredit()){
                        $amount             = $cartPurchase->getActualAmount();
                        $payableAmount      = $cartPurchase->getPayableAmount();
                
                        $tempArr = array();
                        $tempArr['amount']           = $amount;
                        $tempArr['payableAmount']    = $payableAmount;
                        $tempArr['credit']           = $cartPurchase->getCredit()->getCredit();
                
                        $accountSummary['Cart']['Credit'][]         = $tempArr;
                        $accountSummary['IsCreditAvailabledInCart'] = 1;
                        $accountSummary['TotalCartAmount'] += $payableAmount;
                    }
                }
            }                        
        }
        //End here
        
        $userPurchaseItem = $this->em->getRepository('LostUserBundle:UserService')->getUsersPurchasedService($user,false);
                
        if($userPurchaseItem){
            
            $accountSummary['PurchasedAvailable'] = 1;
            $totalPurchaseAmount = 0;
            foreach ($userPurchaseItem as $purchase){
                
                if(in_array($purchase->getService()->getName(),$accountSummary['AvailableServicesOnLocation'])){
                    
                    $serviceId   = $purchase->getService()->getId();
                    $serviceName = $purchase->getService()->getName();
                    $isAddOn     = $purchase->getIsAddon();
                    $packageName = $purchase->getPackageName();
                    $packageId   = $purchase->getPackageId();
                    $amount      = $purchase->getActualAmount();
                    $discountPercentage = $purchase->getDiscountRate();
                    $discountAmount     = $purchase->getTotalDiscount();
                
                    $tempArr = array();
                    $tempArr['serviceName']         = $serviceName;
                    $tempArr['serviceId']           = $serviceId;
                    $tempArr['packageName']         = $packageName;
                    $tempArr['packageId']           = $packageId;
                    $tempArr['amount']              = $amount;
                    $tempArr['discountPercentage']  = $discountPercentage;
                    $tempArr['discountAmount']      = $discountAmount;
                    $tempArr['userServiceId']       = $purchase->getId();
                    $tempArr['bandwidth']           = $purchase->getBandwidth();
                    $tempArr['validity']            = $purchase->getValidity();
                    $tempArr['userserviceId']       = $purchase->getId();
                
                    if($discountPercentage){
                        //$amount = $amount - $discountAmount;
                    }
                    $totalPurchaseAmount = $totalPurchaseAmount + $amount;
                    
                    //Calculate remain days and credit of current package
                    $currentDate    = new DateTime();
                    $activationDate = $purchase->getActivationDate();
                    $daysInterval = $currentDate->diff($activationDate);
                    
                    $noOfdaysUsed = $daysInterval->format('%a');
                    
                    
                    $perDayPrice  = 0;
                    $remainigDays = 0;
                    $remainingCredits = 0;
                    
                    if($purchase->getValidity() && $noOfdaysUsed <= $purchase->getValidity()){
                    
                        $remainigDays = $purchase->getValidity() - $noOfdaysUsed;
                    
                        $perDayPrice = number_format($purchase->getActualAmount() / $purchase->getValidity(),2);
                    }
                    $remainingCredits = number_format($perDayPrice * $remainigDays,2);
                    //End here
                
                    if(strtoupper($serviceName) == 'ISP'){
                        $accountSummary['IPTVButtonEnabled'] = 1;
                    }
                
                    array_push($accountSummary['ServiceAvailableInPurchased'],strtoupper($serviceName));
                
                    $accountSummary['TotalPurchasedAmount'] = $totalPurchaseAmount;
                
                    if($isAddOn){
                        
                        $addonsRemainingCredit = $perDayPrice * $accountSummary['Purchased']['IPTVRemainDays'];
                        if($addonsRemainingCredit > 5){
                            $remainingCredits = $addonsRemainingCredit;
                        }
                
                        $accountSummary['PurchasedAddOnPackageId'][] = $packageId;
                        $accountSummary['IsAddOnAvailabledInPurchased'] = 1;
                        $accountSummary['Purchased'][strtoupper($serviceName)]['AddOnPack'][] = $tempArr;
                        $accountSummary['Purchased'][strtoupper($serviceName)]['CurrentAddOnPackbandwidth'] = $purchase->getBandwidth();
                        $accountSummary['Purchased'][strtoupper($serviceName)]['CurrentAddOnPackvalidity']  = $purchase->getValidity();
                        $accountSummary['Purchased'][strtoupper($serviceName)]['CurrentAddOnPackprice']     = $purchase->getActualAmount();
                        
                        $accountSummary['Purchased']['AddOnPackPerDayPrice'] = $perDayPrice;
                        $accountSummary['Purchased']['AddOnPackRemainDays']  = $remainigDays;
                        $accountSummary['Purchased']['AddOnPackRemainCredit'] = $remainingCredits;
                                                                                    
                    }else{
                        $accountSummary['Is'.strtoupper($serviceName).'AvailabledInPurchased'] = 1;
                        $accountSummary['Purchased'.strtoupper($serviceName).'PackageId'][] = $packageId;
                        $accountSummary['Purchased'][strtoupper($serviceName)]['RegularPack'][] = $tempArr;
                        $accountSummary['Purchased'][strtoupper($serviceName)]['Current'.strtoupper($serviceName).'bandwidth'] = $purchase->getBandwidth();
                        $accountSummary['Purchased'][strtoupper($serviceName)]['Current'.strtoupper($serviceName).'validity']  = $purchase->getValidity();
                        $accountSummary['Purchased'][strtoupper($serviceName)]['Current'.strtoupper($serviceName).'price']     = $purchase->getActualAmount();
                        
                        $accountSummary['Purchased'][strtoupper($serviceName).'PerDayPrice'] = $perDayPrice;
                        $accountSummary['Purchased'][strtoupper($serviceName).'RemainDays']  = $remainigDays;
                        $accountSummary['Purchased'][strtoupper($serviceName).'RemainCredit'] = $remainingCredits;                    
                    }  
                }                          
            }
                        
        }
        
        //echo "<pre>";print_r($accountSummary);exit;
        return $accountSummary;
    }
    
    public function checkISPAddedForIPTV(){
        
        $summaryData = $this->getUserServiceSummary();
        
        //Check ISP service added or not for IPTV
        if(in_array('IPTV',$summaryData['AvailableServicesOnLocation']) && in_array('ISP',$summaryData['AvailableServicesOnLocation'])){
            
            if($summaryData['IsIPTVAvailabledInCart'] == 1){
            
                if(!in_array('ISP',$summaryData['ServiceAvailableInPurchased']) && !in_array('ISP',$summaryData['ServiceAvailableInCart'])){
                    
                    return false;
                }
            }            
        }
        
        return true;
    }
    
    public function upgradeIPTVServicePlan($objServicePurchase,$servicePlanData){
                
        $summaryData = $this->getUserServiceSummary();
        $sessionId = $this->session->get('sessionId');
        $user      = $this->securitycontext->getToken()->getUser();
        
        
        $servicePlanData['PayableAmount'] = $servicePlanData['ActualAmount'] - $summaryData['Purchased']['IPTVRemainCredit'];
        $servicePlanData['User']          = $user;
        $servicePlanData['UnusedDays']    = $summaryData['Purchased']['IPTVRemainDays'];
        $servicePlanData['UnusedCredit']  = $summaryData['Purchased']['IPTVRemainCredit'];
        //Update IPTV data
        $this->updateServicePurchaseData($objServicePurchase,$servicePlanData);
        
        //Add Activity Log
        $activityLog = array();
        $activityLog['user'] 	    = $user;
        $activityLog['activity']    = 'Add to Cart IPTV';
        $activityLog['description'] = 'User '.$user->getUserName().' add to cart IPTV plan for Upgrade.';
        $this->ActivityLog->saveActivityLog($activityLog);
        //Activity Log end here
        
        if($this->checkISPAddedForIPTV()){
            
            //$objService = $this->em->getRepository('LostUserBundle:Service')->findOneByName('ISP');
            if($summaryData['IsISPAvailabledInCart'] == 0 && $summaryData['IsISPAvailabledInPurchased'] == 1){
                
                $objServicePurchase = new ServicePurchase();
                
                $ISPPackages = $summaryData['Purchased']['ISP']['RegularPack'];
                
                if($ISPPackages){
                    
                    foreach ($ISPPackages as $ISPPackage){
                        
                        if($servicePlanData['Validity'] > $summaryData['Purchased']['ISPRemainDays']){
    
                            $ispPurchasedService = $this->em->getRepository('LostUserBundle:UserService')->find($ISPPackage['userserviceId']);
                            
                            $objServicePurchase = new ServicePurchase();
                            
                            $servicePlanData = array();
                            $servicePlanData['User']          = $user;
                            $servicePlanData['Service']       = $ispPurchasedService->getService();
                            $servicePlanData['PackageId']     = $ispPurchasedService->getPackageId();
                            $servicePlanData['PackageName']   = $ispPurchasedService->getPackageName();
                            $servicePlanData['ActualAmount']  = $ispPurchasedService->getActualAmount();
                            $servicePlanData['PayableAmount'] = $ispPurchasedService->getActualAmount() - $summaryData['Purchased']['ISPRemainCredit'];
                            $servicePlanData['TermsUse']      = 1;
                            $servicePlanData['Bandwidth']     = $ispPurchasedService->getBandwidth();
                            $servicePlanData['Validity']      = $ispPurchasedService->getValidity();
                            $servicePlanData['SessionId']     = $sessionId;
                            $servicePlanData['IsUpgrade']     = 1;
                            $servicePlanData['UnusedDays']    = $summaryData['Purchased']['ISPRemainDays'];
                            $servicePlanData['UnusedCredit']  = $summaryData['Purchased']['ISPRemainCredit'];
                            
                            $this->updateServicePurchaseData($objServicePurchase,$servicePlanData);
                        }
                    }
                    
                    //Add Activity Log
                    $activityLog = array();
                    $activityLog['user'] 	    = $user;
                    $activityLog['activity']    = 'Add to Cart ISP';
                    $activityLog['description'] = 'ISP plan added into cart along with IPTV plan Upgrade.';
                    $this->ActivityLog->saveActivityLog($activityLog);
                    //Activity Log end here
                }                
            }             
        }                    
    }
    
    public function upgradeISPServicePlan($objServicePurchase,$servicePlanData){
        
        $summaryData = $this->getUserServiceSummary();
        $sessionId   = $this->session->get('sessionId');
        $user        = $this->securitycontext->getToken()->getUser();
        //echo "<pre>";print_r($summaryData);exit;
        
        if($summaryData['IsISPAvailabledInPurchased'] == 1){

            $servicePlanData['PayableAmount'] = $servicePlanData['ActualAmount'] - $summaryData['Purchased']['ISPRemainCredit'];
            $servicePlanData['User']          = $user;
            $servicePlanData['UnusedDays']    = $summaryData['Purchased']['ISPRemainDays'];
            $servicePlanData['UnusedCredit']  = $summaryData['Purchased']['ISPRemainCredit'];
            
            $this->updateServicePurchaseData($objServicePurchase,$servicePlanData);
            
            //Add Activity Log
            $activityLog = array();
            $activityLog['user'] 	    = $user;
            $activityLog['activity']    = 'Add to Cart ISP';
            $activityLog['description'] = 'User '.$user->getUserName().' add to cart ISP plan for Upgrade.';
            $this->ActivityLog->saveActivityLog($activityLog);
            //Activity Log end here
        }
    }
    
    public function addNewServicePlan($objServicePurchase,$servicePlanData){
        
        $summaryData = $this->getUserServiceSummary();
        $sessionId   = $this->session->get('sessionId');
        $user        = $this->securitycontext->getToken()->getUser();
        
        $servicePlanData['PayableAmount'] = $servicePlanData['ActualAmount'];
        $servicePlanData['User']          = $user;
        $servicePlanData['UnusedDays']    = NULL;
        $servicePlanData['UnusedCredit']  = NULL;
        
        $this->updateServicePurchaseData($objServicePurchase,$servicePlanData);
        
        if($servicePlanData){
                    
            //Add Activity Log
            $activityLog = array();
            $activityLog['user'] 	    = $user;
            $activityLog['activity']    = 'Add to Cart '.$servicePlanData['Service']->getName();
            $activityLog['description'] = 'User '.$user->getUserName().' add to cart new '.$servicePlanData['Service']->getName().' plan.';
            $this->ActivityLog->saveActivityLog($activityLog);
            //Activity Log end here                        
        }
        
    }
    
    public function addPremiumPackage($objService,$premiumPackageIds,$premiumPackageNameArr,$premiumPriceArr,$premiumPackageValidityArr){
        
        $summaryData = $this->getUserServiceSummary();
        $user = $this->securitycontext->getToken()->getUser();
        $sessionId   = $this->session->get('sessionId');
        
        if($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsIPTVAvailabledInPurchased'] == 1){
        
            $addOnDeleteIds = array();
            if($summaryData['IsAddOnAvailabledInCart'] == 1){
            
                $iptvPackages = $summaryData['Cart']['IPTV'];
            
                if(!empty($iptvPackages['AddOnPack'])){
            
                    foreach ($iptvPackages['AddOnPack'] as $addOnPackage){
            
                        if(!in_array($addOnPackage['packageId'],$premiumPackageIds)){
            
                            $objDeleteAddOnPackage = $this->em->getRepository('LostServiceBundle:ServicePurchase')->find($addOnPackage['servicePurchaseId']);
                            $this->em->remove($objDeleteAddOnPackage);
                            $this->em->flush();
            
                        }
                    }
                }
            }
            
            if(isset($premiumPackageIds) && !empty($premiumPackageIds)){
            
                foreach ($premiumPackageIds as $packageId){
            
                    $condition = array('packageId' => $packageId,'sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 1);
                    $objAddOnServicePurchase = $this->em->getRepository('LostServiceBundle:ServicePurchase')->findOneBy($condition);
            
                    if(!$objAddOnServicePurchase){
            
                        $objAddOnServicePurchase = new ServicePurchase();
                    }
                    
                    $PayableAmount      = $premiumPriceArr[$packageId];
                    $PackageName        = $premiumPackageNameArr[$packageId];
                    $PackageValidity    = $premiumPackageValidityArr[$packageId];
                    $PackagePrice       = $premiumPriceArr[$packageId];
                    $premiumPerDayPrice = $PackagePrice / $PackageValidity;
                    $UnusedDays = NULL;
                    $UnusedCredit = NULL;
                    
                    if($summaryData['IsIPTVAvailabledInCart'] == 1){
                        
                        $PayableAmount = $premiumPerDayPrice * $summaryData['Cart']['IPTV']['CurrentIPTVPackvalidity'];
                        $PackageValidity = $summaryData['Cart']['IPTV']['CurrentIPTVPackvalidity'];
                                                
                    }elseif($summaryData['IsIPTVAvailabledInPurchased'] == 1){
                        
                        $PayableAmount = $premiumPerDayPrice * $summaryData['Purchased']['IPTVRemainDays'];
                        $PackageValidity = $summaryData['Purchased']['IPTVRemainDays'];
                    }
                    
                    if($PayableAmount){
                        
                        $servicePlanData = array();
                        $servicePlanData['User']          = $user;
                        $servicePlanData['Service']       = $objService;
                        $servicePlanData['PackageId']     = $packageId;
                        $servicePlanData['PackageName']   = $premiumPackageNameArr[$packageId];
                        $servicePlanData['ActualAmount']  = $premiumPriceArr[$packageId];
                        $servicePlanData['PayableAmount'] = $PayableAmount;
                        $servicePlanData['TermsUse']      = 1;
                        $servicePlanData['Bandwidth']     = NULL;
                        $servicePlanData['Validity']      = $PackageValidity;
                        $servicePlanData['SessionId']     = $sessionId;
                        $servicePlanData['IsUpgrade']     = 0;
                        $servicePlanData['IsAddon']       = 1;
                        $servicePlanData['UnusedDays']    = $UnusedDays;
                        $servicePlanData['UnusedCredit']  = $UnusedCredit;
                
                        $this->updateServicePurchaseData($objAddOnServicePurchase,$servicePlanData);
                        
                        //Add Activity Log
                        $activityLog = array();
                        $activityLog['user'] 	    = $user;
                        $activityLog['activity']    = 'Add to Cart AddOns Package';
                        $activityLog['description'] = 'User '.$user->getUserName().' add to cart AddOns Package.';
                        $this->ActivityLog->saveActivityLog($activityLog);
                        //Activity Log end here
                    }
                }
            }
        } 
    }
    
    public function updateServicePurchaseData($objServicePurchase,$servicePlanData){
        
        $objServicePurchase->setUser($servicePlanData['User']);
        $objServicePurchase->setService($servicePlanData['Service']);
        $objServicePurchase->setPackageId($servicePlanData['PackageId']);
        $objServicePurchase->setPackageName($servicePlanData['PackageName']);
        $objServicePurchase->setPaymentStatus('New');        
        $objServicePurchase->setActualAmount($servicePlanData['ActualAmount']);
        $objServicePurchase->setPayableAmount($servicePlanData['PayableAmount']);
        $objServicePurchase->setTermsUse($servicePlanData['TermsUse']);
        $objServicePurchase->setBandwidth($servicePlanData['Bandwidth']);
        $objServicePurchase->setValidity($servicePlanData['Validity']);
        $objServicePurchase->setSessionId($servicePlanData['SessionId']);
        $objServicePurchase->setIsUpgrade($servicePlanData['IsUpgrade']);
        $objServicePurchase->setUnusedDays($servicePlanData['UnusedDays']);
        $objServicePurchase->setUnusedCredit($servicePlanData['UnusedCredit']);
        
        
        if(isset($servicePlanData['IsAddon']) && !empty($servicePlanData['IsAddon'])){
            
            $objServicePurchase->setIsAddon($servicePlanData['IsAddon']);
        }
        
        $this->em->persist($objServicePurchase);
        $this->em->flush();
        
        $insertIdServicePurchase = $objServicePurchase->getId();
        
        if($insertIdServicePurchase){
            
            return $insertIdServicePurchase;
        }
        
        return false;
    }
}
