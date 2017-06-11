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
use Lost\ServiceBundle\Entity\PurchaseOrder;

class ServiceController extends Controller {

    protected $failedErrNo = array('1001', '1002', '1003');

    public function packageAction(Request $request, $service, $addon) {
        
        $service = strtoupper($service);
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        
        $discount = $this->get('BundleDiscount')->getBundleDiscount();
        $availableService = $this->get('UserLocationWiseService')->getUserLocationService();
            
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        //echo "<pre>";print_r($summaryData);exit;
        if(empty($summaryData['AvailableServicesOnLocation'])){
            
            $this->get('session')->getFlashBag()->add('notice', 'IPTV/Internet service not available at your location.');
            return $this->redirect($this->generateUrl('lost_user_account'));
        }
        if(!in_array($service,$summaryData['AvailableServicesOnLocation'])){
        
            throw $this->createNotFoundException('Invalid Page Request');
        }
        
        
        $myPackages = array();
        $allPackages = array();
        
        $em = $this->getDoctrine()->getManager();
        
        // check if service is IPTV
        if ($service == 'IPTV') {
            
            if(!$this->get('DashboardSummary')->checkISPAddedForIPTV()){

                $this->get('session')->getFlashBag()->add('notice', 'Internet is required for IPTV at your location.');
                return $this->redirect($this->generateUrl('lost_user_account'));
            }
            
            $wsParam = array();
            $selevisionService = $this->get('selevisionService');
            $allPackages = $selevisionService->getAllPackageDetails();                       
        }
         
        if ($service == 'ISP') {
            
            $allPackages = array();
            
            $allPackages['ISP-Package-1']['packageId']     = '101';
            $allPackages['ISP-Package-1']['packageName']   = 'ISP-Package-1';
            $allPackages['ISP-Package-1']['packagePrice']  = '10';
            $allPackages['ISP-Package-1']['bandwidth']     = '10';
            $allPackages['ISP-Package-1']['validity']     = '30';
            
            
            $allPackages['ISP-Package-2']['packageId']     = '102';
            $allPackages['ISP-Package-2']['packageName']   = 'ISP-Package-2';
            $allPackages['ISP-Package-2']['packagePrice']  = '15';
            $allPackages['ISP-Package-2']['bandwidth']     = '15';
            $allPackages['ISP-Package-2']['validity']     = '45';
            
            $allPackages['ISP-Package-3']['packageId']     = '103';
            $allPackages['ISP-Package-3']['packageName']   = 'ISP-Package-3';
            $allPackages['ISP-Package-3']['packagePrice']  = '40';
            $allPackages['ISP-Package-3']['bandwidth']     = '25';
            $allPackages['ISP-Package-3']['validity']     = '60';
            
            $allPackages['ISP-Package-4']['packageId']     = '104';
            $allPackages['ISP-Package-4']['packageName']   = 'ISP-Package-4';
            $allPackages['ISP-Package-4']['packagePrice']  = '60';
            $allPackages['ISP-Package-4']['bandwidth']     = '40';
            $allPackages['ISP-Package-4']['validity']     = '90';
            
        }
        
        
        $packageColumnArray = array('Package', 'Price', 'Number Of Channels');

        $premiumPackage = array();
        
        $premiumPackage[1]['packageId']     = '9001';
        $premiumPackage[1]['packageName']   = 'HBO';
        $premiumPackage[1]['packagePrice']  = '11';
        $premiumPackage[1]['validity']      = '30';
        
        $premiumPackage[2]['packageId']     = '9002';
        $premiumPackage[2]['packageName']   = 'STAR';
        $premiumPackage[2]['packagePrice']  = '12';
        $premiumPackage[2]['validity']      = '30';
        
        $premiumPackage[3]['packageId']     = '9003';
        $premiumPackage[3]['packageName']   = 'ZEE';
        $premiumPackage[3]['packagePrice']  = '13';
        $premiumPackage[3]['validity']      = '30';

        
        
        return $this->render('LostServiceBundle:Service:plan.html.twig', array(
                    'packageColumnArray'  => $packageColumnArray,
                    'allPackages'         => $allPackages,
                    'premiumPackage'       => $premiumPackage,
                    'addonPrimium'        => $addon == 1 ? 1 : 0,
                    'discount'            => ($discount)?$discount['Precentage']:'',
                    'summaryData'         => $summaryData
        ));
    }
    
    // package list
    public function packageSelectAction(Request $request, $service) {
    
        $service   = strtoupper($service);
        $em        = $this->getDoctrine()->getManager();
        $user      = $this->get('security.context')->getToken()->getUser();
        $sessionId = $this->get('paymentProcess')->generateCartSessionId();
    
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        
        if(!in_array(strtoupper($service),$summaryData['AvailableServicesOnLocation'])){
    
            throw $this->createNotFoundException('Invalid Page Request');
        }
        
        if ($request->getMethod() == "POST") {
    
            $objService = $em->getRepository('LostUserBundle:Service')->findOneByName($service);
    
            if($objService){
    
                $packageNameArr        = $request->get('packageName');
                $packagePriceArr       = $request->get('price');
                $bandwidthArr          = $request->get('bandwidth');
                $validityArr           = $request->get('validity');
                $premiumPackageNameArr = $request->get('premiumPackageName');
                $premiumPriceArr       = $request->get('premiumPrice');
                $premiumPackageValidityArr = $request->get('premiumPackageValidity');
                
    
                $packageId            = $request->get('packageId');
                $premiumPackageIds    = $request->get('premiumPackageId');
                $termsUse             = $request->get('termsUse');
                
                $flagError = 0;
                
                if($service == 'IPTV'){

                    if($summaryData['IsIPTVAvailabledInPurchased'] == 0 && $summaryData['IsIPTVAvailabledInCart'] == 0){
                    
                        if($packageId == ''){

                            $this->get('session')->getFlashBag()->add('failure', 'Please select valid IPTV package.');
                            $flagError = 1;
                        }                        
                    }
                }else if($service == 'ISP' && $packageId == ''){
                    
                    $this->get('session')->getFlashBag()->add('failure', 'Please select valid ISP package.');
                    $flagError = 1;
                }else if($termsUse != 1){
    
                    $this->get('session')->getFlashBag()->add('failure', 'Please accept terms and use.');
                    $flagError = 1;
                }
    
                if(!$flagError){
                    
                    $this->get('session')->set('termsUse',$termsUse);
                    
                    if($packageId){
                        
                        $price = 0;
                        if($packagePriceArr[$packageId] > 0){
                        
                            $price = $packagePriceArr[$packageId];                        
                        }
                        
                        //Bandwidth for ISP pack
                        $bandwidth = NULL;
                        if (isset($bandwidthArr[$packageId]) && !empty($bandwidthArr[$packageId])){
                        
                            $bandwidth = $bandwidthArr[$packageId];
                        }
                        
                        $validity = NULL;
                        if (isset($validityArr[$packageId]) && !empty($validityArr[$packageId])){
                        
                            $validity = $validityArr[$packageId];
                        }
                        
                        $servicePlanData = array();
                        
                        $servicePlanData['Service']       = $objService;
                        $servicePlanData['PackageId']     = $packageId;
                        $servicePlanData['PackageName']   = $packageNameArr[$packageId];
                        $servicePlanData['ActualAmount']  = $price;
                        $servicePlanData['TermsUse']      = $termsUse;
                        $servicePlanData['Bandwidth']     = $bandwidth;
                        $servicePlanData['Validity']      = $validity;
                        $servicePlanData['SessionId']     = $sessionId;
                        $servicePlanData['IsUpgrade']     = 0;
                        
                        $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 0);
                        $objServicePurchase = $em->getRepository('LostServiceBundle:ServicePurchase')->findOneBy($condition);
                        
                        if (!$objServicePurchase) {
                        
                            $objServicePurchase = new ServicePurchase();
                        }
                        
                        if($summaryData['Is'.strtoupper($service).'AvailabledInPurchased'] == 1){
                            
                            $servicePlanData['IsUpgrade'] = 1;
                            
                            if(strtoupper($service) == 'IPTV'){
    
                                $savedResult = $this->get('DashboardSummary')->upgradeIPTVServicePlan($objServicePurchase,$servicePlanData);                                                        
                            }
                            
                            if(strtoupper($service) == 'ISP'){
                            
                                $savedResult = $this->get('DashboardSummary')->upgradeISPServicePlan($objServicePurchase,$servicePlanData);
                            }                        
                        }else{
                            
                            $savedResult = $this->get('DashboardSummary')->addNewServicePlan($objServicePurchase,$servicePlanData);                                                
                        }
                    }
                    //Save AddOn Package
                    if(isset($premiumPackageIds) && !empty($premiumPackageIds)){
                    
                        $savedResult = $this->get('DashboardSummary')->addPremiumPackage($objService,$premiumPackageIds,$premiumPackageNameArr,$premiumPriceArr,$premiumPackageValidityArr);
                    }
                    
                    if($savedResult){
    
                        $this->get('session')->getFlashBag()->add('success', 'Package data has been saved successfully.');
                    }
    
                    //Update Discount data
                    $discount = $this->get('BundleDiscount')->getBundleDiscount();
    
                    return $this->redirect($this->generateUrl('lost_user_account'));
                }else{
    
                    return $this->redirect($this->generateUrl('lost_service_plan', array('service' => $service)));
                }
            }else{
    
                throw $this->createNotFoundException('Invalid Page Request');
            }
        }else{
            
            throw $this->createNotFoundException('Invalid Page Request');
        }
    }
    
    public function purchaseverificationAction(Request $request) {
        
        $user       = $this->get('security.context')->getToken()->getUser();
        $em         = $this->getDoctrine()->getManager();
        $sessionId  = $this->get('session')->get('sessionId'); //Get Session Id
        
        $isDeersAuthenticatedForIPTV = $this->get('DeersAuthentication')->isDeersAuthenticatedForIPTV();
        $userServiceLocation         = $this->get('UserLocationWiseService')->getUserLocationService();        
        $summaryData                 = $this->get('DashboardSummary')->getUserServiceSummary();
        $isISPAddedForIPTV           = $this->get('DashboardSummary')->checkISPAddedForIPTV();
        
        if(!$isISPAddedForIPTV){
        
            throw $this->createNotFoundException('Invalid Page Request');
        }
        
        //echo "<pre>";print_r($summaryData);exit;

        $form = $this->createFormBuilder(array())->getForm();

        $objServicePurchase = $em->getRepository('LostServiceBundle:ServicePurchase')->findby(array('user' => $user, 'paymentStatus' => 'New', 'sessionId' => $sessionId));

        return $this->render('LostServiceBundle:Service:purchaseConfirm.html.twig', array(
                        'objServicePurchase'          => $objServicePurchase,
                        'form'                        => $form->createView(),
                        'expiresMonth'                => $this->creditCardYearMonth('month'),
                        'expiresYear'                 => $this->creditCardYearMonth('year'),
                        'country'                     => $em->getRepository('LostUserBundle:Country')->getCreditCardCountry(),
                        'summaryData'                 => $summaryData,
                        'isISPAddedForIPTV'           => $isISPAddedForIPTV,
                        'userServiceLocation'         => $userServiceLocation,
                        'isDeersAuthenticatedForIPTV' => $isDeersAuthenticatedForIPTV,
                        'creditBalance'               => ($user->getUserCredit())?$user->getUserCredit()->getTotalCredits():0
                    )
        );
    }
    
    public function orderComfirmationAction() {
    
        //Clear Session Data
        $this->get('PaymentProcess')->clearPaymentSession();
    
        $view = array();
    
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->getRequest()->get('ord');
    
        $purchaseOrder = $em->getRepository('LostServiceBundle:PurchaseOrder')->findOneByOrderNumber($orderNumber);
    
        if (!$purchaseOrder) {
    
            throw $this->createNotFoundException('Invalid Page Request');
        }
        
        $purchasedSummaryData = array();
        
        $purchasedSummaryData['OrderNumber'] = '';
        $purchasedSummaryData['PurchasedDate'] = '';
        $purchasedSummaryData['PaymentMethod'] = '';
        $purchasedSummaryData['ServiceNetPaidAmount'] = 0;
        $purchasedSummaryData['ServiceNetRefundedAmount'] = 0;
        $purchasedSummaryData['PurchaseOrderNetPaidAmount'] = 0;
        $purchasedSummaryData['PurchaseOrderNetRefundedAmount'] = 0;
        $purchasedSummaryData['IPTVTotalPaybleAmount']     = 0;
        $purchasedSummaryData['IPTVTotalActualAmount']     = 0;
        $purchasedSummaryData['IPTVTotalRefundedAmount']   = 0;
        $purchasedSummaryData['AddOnTotalPaybleAmount']    = 0;
        $purchasedSummaryData['AddOnTotalActualAmount']    = 0;
        $purchasedSummaryData['AddOnTotalRefundedAmount']  = 0;        
        $purchasedSummaryData['ISPTotalPaybleAmount']      = 0;
        $purchasedSummaryData['ISPTotalActualAmount']      = 0;
        $purchasedSummaryData['ISPTotalRefundedAmount']    = 0;
        $purchasedSummaryData['CreditTotalPaybleAmount']   = 0;
        $purchasedSummaryData['CreditTotalActualAmount']   = 0;
        $purchasedSummaryData['CreditTotalRefundedAmount'] = 0;
        $purchasedSummaryData['NetPaidAmount']             = 0;
        
        if($purchaseOrder){

            if($purchaseOrder->getServicePurchases()){

                $i = 0;
                foreach ($purchaseOrder->getServicePurchases() as $servicePurchased){
                    
                    $tempArr = array();
                    $tempArr['packageName']         = $servicePurchased->getPackageName();
                    $tempArr['packageStatus']       = $servicePurchased->getActivationStatus();
                    $tempArr['packagePaybleAmount'] = $servicePurchased->getPayableAmount();
                    $tempArr['packageActualAmount'] = $servicePurchased->getActualAmount();
                    $tempArr['paymentStatus']       = $servicePurchased->getPaymentStatus();
                    if($servicePurchased->getPaymentStatus() == 'Refunded'){
                        $tempArr['RefundedAmount']      = $servicePurchased->getPayableAmount();
                    }else{
                        $tempArr['RefundedAmount']      = 0;
                    }
                    
                    if($servicePurchased->getService()){

                        if($servicePurchased->getIsAddon() == 1){
                            $serviceName = 'AddOn';
                        }else{
                            $serviceName = strtoupper($servicePurchased->getService()->getName());
                        }
                        

                        $purchasedSummaryData[$serviceName][] = $tempArr;
                        $purchasedSummaryData[$serviceName.'TotalPaybleAmount'] += $servicePurchased->getPayableAmount();
                        $purchasedSummaryData[$serviceName.'TotalActualAmount'] += $servicePurchased->getActualAmount();
                        $purchasedSummaryData[$serviceName.'TotalRefundedAmount'] += $tempArr['RefundedAmount'];
                        
                        
                    }
                    if($servicePurchased->getIsCredit() == 1 && $servicePurchased->getCredit()){
                        
                        $credit = $servicePurchased->getCredit()->getCredit();
                        
                        $tempArr['packageName'] = 'Pay $'.$servicePurchased->getPayableAmount().' and get $'.$credit.' credit in your account.';
                        $purchasedSummaryData['Credit'][] = $tempArr;
                        $purchasedSummaryData['CreditTotalPaybleAmount'] += $servicePurchased->getPayableAmount();
                        $purchasedSummaryData['CreditTotalActualAmount'] += $servicePurchased->getActualAmount();
                        $purchasedSummaryData['CreditTotalRefundedAmount'] += $tempArr['RefundedAmount'];
                    }
                    $i++;                                        
                }

                $purchasedSummaryData['ServiceNetPaidAmount']     = $purchasedSummaryData['IPTVTotalPaybleAmount'] + $purchasedSummaryData['AddOnTotalPaybleAmount'] + $purchasedSummaryData['ISPTotalPaybleAmount'] + $purchasedSummaryData['CreditTotalPaybleAmount'];
                $purchasedSummaryData['ServiceNetRefundedAmount'] = $purchasedSummaryData['IPTVTotalRefundedAmount'] + $purchasedSummaryData['AddOnTotalRefundedAmount'] + $purchasedSummaryData['ISPTotalRefundedAmount'] + $purchasedSummaryData['CreditTotalRefundedAmount'];
            }        

            $purchasedSummaryData['OrderNumber']                     = $purchaseOrder->getOrderNumber();
            $purchasedSummaryData['PurchasedDate']                   = $purchaseOrder->getCreatedAt()->format('m/d/Y h:i:s');
            $purchasedSummaryData['PaymentMethod']                   = $purchaseOrder->getPaymentMethod()->getName();
            $purchasedSummaryData['PurchaseOrderNetPaidAmount']      = $purchaseOrder->getTotalAmount();
            $purchasedSummaryData['PurchaseOrderNetRefundedAmount']  = $purchaseOrder->getRefundAmount();            
        }
        //echo "<pre>";print_r($purchasedSummaryData);exit;
        
    
        $view['purchaseOrder'] = $purchaseOrder;
        $view['purchasedSummaryData'] = $purchasedSummaryData;
    
        return $this->render('LostServiceBundle:Service:purchaseSuccess.html.twig', $view);
    }
    
    public function paymentCancelAction($id) {
    
        $user      = $this->get('security.context')->getToken()->getUser();
        $em        = $this->getDoctrine()->getManager();
        $sessionId = $this->get('session')->get('sessionId'); //Get Session Id
    
        $this->get('session')->getFlashBag()->add('failure', 'Your payment has been cancelled.');
        return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
    }
    
    
    
    public function purchaseFailAction() {
    
        //Clear Session Data
        $this->get('PaymentProcess')->clearPaymentSession();
        
        $view = array();
    
        $em          = $this->getDoctrine()->getManager();
        $user        = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->getRequest()->get('ord');
        $errNo       = $this->getRequest()->get('err');
        
        $purchaseOrder = $em->getRepository('LostServiceBundle:PurchaseOrder')->findOneByOrderNumber($orderNumber);
        
        if (!$purchaseOrder) {
        
            throw $this->createNotFoundException('Invalid Page Request');
        }
        
        $view['errNo']         = $errNo;
        $view['purchaseOrder'] = $purchaseOrder;
    
        return $this->render('LostServiceBundle:Service:purchaseFail.html.twig', $view);
    }
    
    public function channelListAction($packageId) {

        
        $wsParam = array();
        $wsParam['offer'] = $packageId;
        $selevisionService = $this->get('selevisionService');
        $wsRespose = $selevisionService->callWSAction('getChannelsFromOffer', $wsParam);
        
        
        $channelList = array();
        
         if (!empty($wsRespose)) {
                
                if(array_key_exists('data', $wsRespose)) {
                    $channelList['channel'] = $wsRespose['data'];   
                }
         }

        $view = array();
        $view['channelList'] = $channelList;
        
        return $this->render('LostServiceBundle:Service:channelList.html.twig', $view);
    }

     public function premiumChannelListAction($packageId) {

        $view = array();
        $view['channelList']['channel'][0] = 'Premium HBO';
        $view['channelList']['channel'][1] = 'Premium ZEE';
        $view['channelList']['channel'][2] = 'Premium STAR';
        
        return $this->render('LostServiceBundle:Service:premiumChannelList.html.twig', $view);
    }
    
    
    public function creditCardYearMonth($type) {

        $data = array();

        if ($type == 'month') {

            for ($i = 1; $i <= 12; $i++) {

                $data[$i] = $i;
            }
        }

        if ($type == 'year') {

            $year = date('Y');
            for ($i = 0; $i <= 10; $i++) {

                $yy = $year + $i;
                $data[$yy] = $yy;
            }
        }

        return $data;
    }

    public function serviceRemoveAction(Request $request, $service) {

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $sessionId = $this->get('session')->get('sessionId'); //Get Session Id
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        
        $addon = $request->get('addon');
        $both = $request->get('both');
        $id = $request->get('id');

        $deleteServiceArr = array();
        if(!in_array(strtoupper($service),$summaryData['AvailableServicesOnLocation'])){
    
            throw $this->createNotFoundException('Invalid Page Request');
        }
        $deleteServiceArr[] = strtoupper($service); 
        
        if($both){
            
            if(!in_array('IPTV',$summaryData['AvailableServicesOnLocation']) && !in_array('ISP',$summaryData['AvailableServicesOnLocation'])){
                
                throw $this->createNotFoundException('Invalid Page Request');
            }
            $deleteServiceArr = $summaryData['AvailableServicesOnLocation'];
        }
        
        $resultDelete = true;
        
        if(isset($deleteServiceArr) && !empty($deleteServiceArr)){
            
            foreach ($deleteServiceArr as $deleteService){
                
                $objService = $em->getRepository('LostUserBundle:Service')->findOneBy(array('name' => strtoupper($deleteService)));
                
                $objDeletePurchase = $em->getRepository('LostServiceBundle:ServicePurchase')->deleteServicePackage($sessionId, $user, $objService, $id, $addon);
                
                if(!$objDeletePurchase){
                    
                    $resultDelete = false;
                }                
            }            
        }
        
        if($resultDelete){
            
            $descriptionLog = 'Removed '.strtoupper($deleteService).' pack from cart';
            $activityTitle = 'User '.$user->getUserName().' has removed '.strtoupper($deleteService).' pack from cart.';
            if($addon){
                
                $activityTitle = 'Removed '.strtoupper($deleteService).' AddOns Plan from cart';
                $descriptionLog = 'User '.$user->getUserName().' has removed '.strtoupper($deleteService).' AddOns pack from cart.';
            }
            
            //Add Activity Log
            $activityLog = array();
            $activityLog['user'] 	    = $user;
            $activityLog['activity']    = $activityTitle;
            $activityLog['description'] = $descriptionLog;
            
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            //Activity Log end here
            
            
            //Update Discount data
            $discount = $this->get('BundleDiscount')->getBundleDiscount();
            
            $this->get('session')->getFlashBag()->add('success', 'Service package has been deleted successfully.');            
        }else{
            
            $this->get('session')->getFlashBag()->add('success', 'Service package has not been deleted successfully.');
        }
        
        return $this->redirect($this->generateUrl('lost_user_account'));                        
    }
    
    public function activeServiceByCreditAction(Request $request) {
    
        $em          = $this->getDoctrine()->getManager();
        $user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        
        $availableCredit = 0;
        $debitedCredit = 0;
        
        if ($request->getMethod() == "POST" && $summaryData) {
            //echo "<pre>";print_r($_POST);exit;
            
            $paybleAmount = $request->get('paybleAmount');
            
            if($paybleAmount == 0){
                
                if($user->getUserCredit() && $summaryData['CartAvailable'] == 1){
                   
                    $cartTotalAmount = $summaryData['TotalCartAmount'];
                    $availableCredit = $user->getUserCredit()->getTotalCredits();
                    
                    if($cartTotalAmount < $availableCredit){

                        $debitedCredit = $cartTotalAmount; 
                    }
                    
                    if($cartTotalAmount >= $availableCredit){
                        
                        $debitedCredit = $availableCredit;
                    }

                    if($debitedCredit){
                        
                        $objPaymentMethod = $em->getRepository('LostServiceBundle:PaymentMethod')->findOneByCode('Credit');
                        
                        $objPurchaseOrder = $em->getRepository('LostServiceBundle:PurchaseOrder')->findOneBy(array('sessionId' => $sessionId, 'paymentStatus' => 'InProcess'));
                        
                        if(!$objPurchaseOrder){

                            $objPurchaseOrder = new PurchaseOrder();
                        }                        
                        $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                        $objPurchaseOrder->setSessionId($sessionId);
                        $objPurchaseOrder->setOrderNumber($orderNumber);
                        $objPurchaseOrder->setUser($user);
                        $objPurchaseOrder->setTotalAmount($cartTotalAmount);
                        
                        $em->persist($objPurchaseOrder);
                        $em->flush();
                        $insertIdPurchaseOrder = $objPurchaseOrder->getId();
                        
                        if($insertIdPurchaseOrder){
                            
                            $this->get('session')->set('PurchaseOrderId',$insertIdPurchaseOrder);
                            $this->get('session')->set('PurchaseOrderNumber',$orderNumber);
                            
                            if($response){
                                
                                $objPurchaseOrder->setPaymentStatus('Completed');
                                $em->persist($objPurchaseOrder);
                                $em->flush();
                               
                                $updateServicePurchase = $em->createQueryBuilder()->update('LostServiceBundle:ServicePurchase', 'sp')
                                                            ->set('sp.purchaseOrder', $insertIdPurchaseOrder)
                                                            ->set('sp.paymentStatus', '\'Completed\'')
                                                            ->where('sp.sessionId =:sessionId')
                                                            ->setParameter('sessionId', $sessionId)
                                                            ->andWhere('sp.paymentStatus =:paymentStatus')
                                                            ->setParameter('paymentStatus', 'New')
                                                            ->andWhere('sp.user =:user')
                                                            ->setParameter('user', $user)
                                                            ->getQuery()->execute();
                                
                                //Activate Purchase packages
                                $paymentRefundStatus = $this->get('packageActivation')->activateServicePack();
                                
                                if($paymentRefundStatus){
                                
                                    $isPaymentRefund = $this->get('paymentProcess')->refundPayment();
                                }
                                
                                return $this->redirect($this->generateUrl('lost_service_purchase_order_confirm',array('ord' => $orderNumber)));                                                                        
                            }else{
                                
                                $em->remove($objPurchaseOrder);
                                $em->flush();
                                
                                //Clear Session Data
                                $this->get('PaymentProcess')->clearPaymentSession();
                                
                                $this->get('session')->getFlashBag()->add('notice', 'Something went wrong in credit debit. please again.');
                                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
                            }                            
                        }                        
                    }
                }else{
                    
                    $this->get('session')->getFlashBag()->add('notice', 'Credit balance or cart data not found.');
                    return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
                }                
            }else{
                
                $this->get('session')->getFlashBag()->add('notice', 'Order could not procced, please check order amount.');
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
            }                        
        }else{

            throw $this->createNotFoundException('Invalid Page Request');
        }        
    }
}
