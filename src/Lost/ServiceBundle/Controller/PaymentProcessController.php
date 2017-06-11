<?php

namespace Lost\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use \DateTime;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\ServiceBundle\Entity\ServicePurchase;
use Symfony\Component\HttpFoundation\Response;
use Lost\ServiceBundle\Model\ExpressCheckout;
use Lost\UserBundle\Entity\User;
use Lost\ServiceBundle\Entity\BillingAddress;
use Lost\ServiceBundle\Form\Type\BillingAddressFormType;
use Lost\ServiceBundle\Entity\Milstar;
use Lost\UserBundle\Entity\UserServiceSetting;
use Lost\UserBundle\Entity\UserServiceSettingLog;
use Lost\UserBundle\Entity\UserService;
use Lost\UserBundle\Entity\UserCreditLog;
use Lost\UserBundle\Entity\UserCredit;

class PaymentProcessController extends Controller {

    protected $container;
    protected $em;
    protected $session;
    protected $securitycontext;
    
    public function __construct($container) {
        
        $this->container = $container;
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        
        $this->DashboardSummary   = $container->get('DashboardSummary');
        $this->GeoLocation        = $container->get('GeoLocation');     
        $this->ActivityLog        = $container->get('ActivityLog');
        //$this->milstar            = $container->get('milstar');
    }
    
    public function getCartItemForPayment(){
        
        $summaryData = $this->DashboardSummary->getUserServiceSummary();
        
        $paymentCart = array();
        $totalCartAmount = 0;
        if($summaryData['CartAvailable'] == 1){
            
            $Cart = $summaryData['Cart'];
            $tempCart = array();
            
            if($summaryData['IsISPAvailabledInCart'] == 1){
                
                foreach ($Cart['ISP']['RegularPack'] as $package){
                    
                    $tempArr['name'] = 'Internet package';
                    $tempArr['desc'] = 'Package: '.$package['packageName'];
                    $tempArr['amt']  = $package['payableAmount'];
                    $totalCartAmount +=$package['payableAmount'];
                    $paymentCart[] = $tempArr; 
                }
            }
            if($summaryData['IsIPTVAvailabledInCart'] == 1){
                
                foreach ($Cart['IPTV']['RegularPack'] as $package){
                
                    $tempArr['name'] = 'IPTV package';
                    $tempArr['desc'] = 'Package: '.$package['packageName'];
                    $tempArr['amt']  = $package['payableAmount'];
                    $totalCartAmount +=$package['payableAmount'];
                    $paymentCart[] = $tempArr;
                }
            }
            if($summaryData['IsAddOnAvailabledInCart'] == 1){
            
                foreach ($Cart['IPTV']['AddOnPack'] as $package){
            
                    $tempArr['name'] = 'IPTV Addons package';
                    $tempArr['desc'] = 'Package: '.$package['packageName'];
                    $tempArr['amt']  = $package['payableAmount'];
                    $totalCartAmount +=$package['payableAmount'];
                    $paymentCart[] = $tempArr;
                }
            }

            if(isset($paymentCart) && !empty($paymentCart)){
                
                if((int)$totalCartAmount == (int) $summaryData['TotalCartAmount']){
                    
                   return $paymentCart;
                }
            }
        }
        return false;        
    }
    
    public function updateServicePurchaseData($queryCase,$updateFields,$type = 'condition'){
    
        //Get Service Purchase
        if($type == 'object'){
    
            $objServicePurchase = $queryCase;
        }else{
    
            $objServicePurchase = $this->em->getRepository('LostServiceBundle:ServicePurchase')->findBy($queryCase);
        }
    
        foreach ($objServicePurchase as $ServicePurchase){
    
            if(array_key_exists('paymentStatus',$updateFields)){
    
                $ServicePurchase->setPaymentStatus($updateFields['paymentStatus']);
            }
            if(array_key_exists('paypalCheckout',$updateFields)){
    
                $ServicePurchase->setPaypalCheckout($updateFields['paypalCheckout']);
            }
    
            if(array_key_exists('sessionId',$updateFields)){
    
                $ServicePurchase->setSessionId($updateFields['sessionId']);
            }
            if(array_key_exists('paymentMethod',$updateFields)){
    
                $ServicePurchase->setPaymentMethod($this->em->getRepository('LostServiceBundle:PaymentMethod')->findOneById($updateFields['paymentMethod']));
            }
        
            $ServicePurchase->setUpdatedAt(new \DateTime(date('Y-m-d H:i:s')));
    
            $this->em->persist($ServicePurchase);
            $this->em->flush();
        }
    }
    
    public function storeMilstarResponse($milsatParams){
        
        $user            = $this->securitycontext->getToken()->getUser();
        $purchaseOrderId = $this->session->get('PurchaseOrderId');
        
        $purchaseOrder = $this->em->getRepository('LostServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        
        if(!$purchaseOrder->getMilstar()){
            
            $objMilstar = new Milstar();
        }else{
            
            $objMilstar = $purchaseOrder->getMilstar();
        }
        
        $objMilstar->setUser($user);
        $objMilstar->setPurchaseOrder($purchaseOrder);
        $objMilstar->setRequestId($milsatParams['requestId']);
        $objMilstar->setPayableAmount($milsatParams['amount']);
        $objMilstar->setFacNbr($milsatParams['processingFacnbr']);
        $objMilstar->setRegion($milsatParams['region']);
        $objMilstar->setZipcode($milsatParams['zipCode']);
        $objMilstar->setCid($milsatParams['cid']);
        
        if(isset($milsatParams['failCode']) && !empty($milsatParams['failCode'])){
            
            if($milsatParams['failCode'] != 'A'){

                $objMilstar->setFailCode((string) $milsatParams['failCode']);
                $objMilstar->setFailMessage((string) $milsatParams['failMessage']);
            }            
        }
        
        if(isset($milsatParams['authCode']) && !empty($milsatParams['authCode'])){
            
            $objMilstar->setAuthCode((string) $milsatParams['authCode']);
            $objMilstar->setAuthTicket((string) $milsatParams['authTicket']);
        }
        
        $objMilstar->setProcessStatus($milsatParams['processStatus']);
        $objMilstar->setResponseCode((string) $milsatParams['responseCode']);
        $objMilstar->setMessage($milsatParams['message']);
        
        $this->em->persist($objMilstar);
        $this->em->flush();
        $insertIdMilstar = $objMilstar->getId();
        
        if($insertIdMilstar){
        
            $purchaseOrder->setMilstar($objMilstar);
            $this->em->persist($purchaseOrder);
            $this->em->flush();
        }
    }
    
    public function storeActiveUserService($servicePurchase){
    
        $ipAddress       = $this->GeoLocation->getIPAddress('ip');
        $user            = $this->securitycontext->getToken()->getUser();
        
        if($servicePurchase){
            
            $currentDate = new \DateTime(date('Y-m-d 23:59:59'));
            $expiryDate  = $currentDate->modify('+'.$servicePurchase->getValidity().' DAYS');
            $activationDate = new \DateTime(date('Y-m-d H:i:s'));
            
            //Add User Service
            $objUserService = $this->em->getRepository('LostUserBundle:UserService')->findOneBy(array('servicePurchase' => $servicePurchase->getId()));
            
            if(!$objUserService){
                
                $objUserService = new UserService();
            }
            
            $objUserService->setUser($user);
            $objUserService->setPurchaseOrder($servicePurchase->getPurchaseOrder());
            $objUserService->setServicePurchase($servicePurchase);
            $objUserService->setService($servicePurchase->getService());                        
            $objUserService->setPackageId($servicePurchase->getPackageId());
            $objUserService->setPackageName($servicePurchase->getPackageName());
            $objUserService->setActualAmount($servicePurchase->getActualAmount());
            $objUserService->setTotalDiscount($servicePurchase->getTotalDiscount());
            $objUserService->setDiscountRate($servicePurchase->getDiscountRate());
            $objUserService->setPayableAmount($servicePurchase->getPayableAmount());
            $objUserService->setBandwidth($servicePurchase->getBandwidth());
            $objUserService->setValidity($servicePurchase->getValidity());
            $objUserService->setActivationDate($activationDate);
            $objUserService->setExpiryDate($expiryDate);
            $objUserService->setStatus(1);
            $objUserService->setIsAddon($servicePurchase->getIsAddon());
            $objUserService->setServiceLocationIp(ip2long($ipAddress));
            $objUserService->setUnusedCredit($servicePurchase->getUnusedCredit());
            $objUserService->setUnusedDays($servicePurchase->getUnusedDays());
            $this->em->persist($objUserService);
            
            $objUserServiceSetting = $this->em->getRepository('LostUserBundle:UserServiceSetting')->findBy(array('user' => $user, 'service' => $servicePurchase->getService()));
            if(!$objUserServiceSetting) {
                //Add Service in UserServiceSetting
                $objUserServiceSetting = new UserServiceSetting();
                $objUserServiceSetting->setService($servicePurchase->getService());
                $objUserServiceSetting->setUser($user);
                $objUserServiceSetting->setServiceStatus('Enabled');
                $this->em->persist($objUserServiceSetting);
            
                //Add User's service log
                $objUserServiceSettingLog = new UserServiceSettingLog();
                $objUserServiceSettingLog->setService($servicePurchase->getService());
                $objUserServiceSettingLog->setUser($user);
                $objUserServiceSettingLog->setServiceStatus('Enabled');
                $this->em->persist($objUserServiceSettingLog);
            }
            
            $this->em->flush();
            
            return true;
        }
        
        return false;
    }
    
    public function refundPayment($refundInfoArr = array()){
        
        
        $user            = $this->securitycontext->getToken()->getUser();
        $purchaseOrderId = $this->session->get('PurchaseOrderId');
        
        $purchaseOrder = $this->em->getRepository('LostServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        
        $totalRefundAmount = 0;
        $totalPaybleAmount = 0;
        $refundSuccess = false;
        $refundType = '';
        $paymentStatus = '';
        
        if($purchaseOrder){
            
            $servicePurchases = $this->em->getRepository('LostServiceBundle:ServicePurchase')->findBy(array('purchaseOrder' => $purchaseOrder->getId()));
            
            if($servicePurchases){                
            
                foreach ($servicePurchases as $order){
                    
                    $totalPaybleAmount += $order->getPayableAmount();
                    
                    if($order->getPaymentStatus() == 'NeedToRefund' && $order->getRechargeStatus() == 2){
                        
                        $totalRefundAmount += $order->getPayableAmount();                        
                    }
                }
                
                $refundType = 'Partial';
                $isRefundCredit = 0;
                $creditRefundType = '';
                if($totalRefundAmount > 0){
                    
                    if($totalRefundAmount == $totalPaybleAmount){
                
                        $refundType = 'Full';
                    }
                    
                    if($purchaseOrder->getPaymentMethod()){
                    
                        $paymentMethod = $purchaseOrder->getPaymentMethod()->getCode();
                        
                        if($paymentMethod == 'PayPal' || $paymentMethod == 'CreditCard'){
                    
                            if($totalRefundAmount > 0){
                                
                                $configPaypal = array(
                                        'METHOD'           => 'RefundTransaction',
                                        'INVNUM'           => $purchaseOrder->getOrderNumber(),
                                        'TRANSACTION_ID'   => $purchaseOrder->getPaypalCheckout()->getPaypalTransactionId(),
                                        'REFUNDTYPE'       => $refundType,
                                        'AMOUNT'           => $totalRefundAmount
                                );
                                
                                $express 		= new ExpressCheckout($configPaypal,$this->container); //Paypal Express Checkout object
                                $paypalResponse = $express->refundPayment();
                        
                                if ($paypalResponse['ACK'] == "Success") {
                                    
                                    $refundSuccess = true;                                                                        
                                }
                            }                            
                        }
                    
                        if($paymentMethod == 'Milstar'){
                    
                            if($purchaseOrder->getMilstar()){
                                
                                if($totalRefundAmount > 0){
                                    
                                    $refundInfoArr['amount'] = 1;
                        
                                    $milstarRefundStatus = $this->get('milstar')->processMilstarCredit($refundInfoArr);
                        
                                    if($milstarRefundStatus){
                        
                                        $refundSuccess = true;                                        
                                    }
                                }
                            }
                        }                                                     
                    }
                    
                    if($refundSuccess){
                    
                        $updateServicePurchase = $this->em->createQueryBuilder()->update('LostServiceBundle:ServicePurchase', 'sp')
                                                    ->set('sp.paymentStatus', '\'Refunded\'')
                                                    ->where('sp.paymentStatus =:paymentStatus')
                                                    ->setParameter('paymentStatus', 'NeedToRefund')
                                                    ->andWhere('sp.rechargeStatus =:rechargeStatus')
                                                    ->setParameter('rechargeStatus', 2)
                                                    ->andWhere('sp.purchaseOrder =:purchaseOrder')
                                                    ->setParameter('purchaseOrder', $purchaseOrderId)
                                                    ->getQuery()->execute();
                        
                        if($refundType == 'Full'){
                            
                            $paymentStatus = 'Refunded';
                        }
                        
                        if($refundType == 'Partial'){
                            
                            $paymentStatus = 'PartiallyCompleted';
                        }
                        
                        //Updtae payment status
                        if($paymentStatus){
                            
                            $purchaseOrder->setPaymentStatus($paymentStatus);
                            $purchaseOrder->setRefundAmount($totalRefundAmount);                            
                            $this->em->persist($purchaseOrder);
                            $this->em->flush();
                            
                            //Add Activity Log
                            $activityLog = array();
                            $activityLog['user'] 	    = $user;
                            $activityLog['activity']    = 'Order#'.$purchaseOrder->getOrderNumber();
                            $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$purchaseOrder->getOrderNumber().' payment has been '.$paymentStatus;
                            $this->ActivityLog->saveActivityLog($activityLog);
                            //Activity Log end here
                        }                        
                    }
                }
            }
        }        
        
        return $refundSuccess;
    }
    
    public function clearPaymentSession(){
        
        $user = $this->securitycontext->getToken()->getUser();
        $sessionId = $this->session->get('sessionId');
        
        $condition = array('user' => $user, 'sessionId' => $sessionId, 'paymentStatus' => 'New');
        $servicePurchase = $this->em->getRepository('LostServiceBundle:ServicePurchase')->findBy($condition);
    
        $this->session->remove('billingInfo');
        $this->session->remove('milstarInfo');
        $this->session->remove('cardInfo');
        $this->session->remove('paymentBy');
    
        $this->session->remove('PurchaseOrderId');
        $this->session->remove('PurchaseOrderNumber');
        $this->session->remove('BillingAddressId');
    
        $this->session->remove('sessionId');
        $this->session->remove('termsUse');
        
        $sessionId = $this->generateCartSessionId();
        if($sessionId){
            
            if($servicePurchase){
                
                foreach ($servicePurchase as $purchase){
                    
                    if(!$purchase->getPurchaseOrder()){
                        
                        $purchase->setSessionId($sessionId);
                        $this->em->persist($purchase);
                        $this->em->flush();
                    }
                }
            }
        }
    }
    
    public function generateCartSessionId(){
        $sessionId =  $this->generateUniqueString(25);
        
        //$sessionId = '2fRJdOlfZZejlj4AJ331wOxN2';
        if(!$this->session->get('sessionId')){
    
            $this->session->set('sessionId',$sessionId);
    
            return $sessionId;
        }else{
    
            return $this->session->get('sessionId');
        }
    }
    
    public function generateOrderNumber(){
        
        $today = date("Ymd");
        $rand = strtoupper(substr(uniqid(sha1(time())),0,4));
        return $today . $rand;
    }
    
    public function generate_random_number($length = 7) {
    
        $character_array = array_merge(range(1, 9));
        $number = "";
        for($i = 0; $i < $length; $i++) {
            $number .= $character_array[rand(0, (count($character_array) - 1))];
        }
        return $number;
    }
    
    public function generateUniqueString($length = 20) {
    
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    
    
    function MaskCreditCard($cc){
        // Get the cc Length
        $cc_length = strlen($cc);
        // Replace all characters of credit card except the last four and dashes
        for($i=0; $i<$cc_length-4; $i++){
            if($cc[$i] == '-'){
                continue;
            }
            $cc[$i] = 'X';
        }
        // Return the masked Credit Card #
        return $cc;
    }
    
    function FormatCreditCard($cc)
    {
        // Clean out extra data that might be in the cc
        $cc = str_replace(array('-',' '),'',$cc);
        // Get the CC Length
        $cc_length = strlen($cc);
        // Initialize the new credit card to contian the last four digits
        $newCreditCard = substr($cc,-4);
        // Walk backwards through the credit card number and add a dash after every fourth digit
        for($i=$cc_length-5;$i>=0;$i--){
            // If on the fourth character add a dash
            if((($i+1)-$cc_length)%4 == 0){
                $newCreditCard = '-'.$newCreditCard;
            }
            // Add the current character to the new credit card
            $newCreditCard = $cc[$i].$newCreditCard;
        }
        // Return the formatted credit card number
        return $newCreditCard;
    }
}
