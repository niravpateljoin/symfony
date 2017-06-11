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
use Lost\ServiceBundle\Entity\PurchaseOrder;
use Symfony\Component\HttpFoundation\Response;
use Lost\ServiceBundle\Model\ExpressCheckout;
use Lost\UserBundle\Entity\User;
use Lost\ServiceBundle\Entity\BillingAddress;
use Lost\ServiceBundle\Form\Type\BillingAddressFormType;

class ConfirmOrderController extends Controller {

    public $paymentStep    = array('1', '2', '3');
    public $paymentOptions = array('1' => 'paypal', '2' => 'cc', '3' => 'milstar');


    public function confirmPaymentDetailAction(Request $request, $step) {

        $jsonResponse = array();
        $jsonResponse['status']           = '';
        $jsonResponse['message']          = '';
        
        $jsonResponse['stepOneResponse']    = '';
        $jsonResponse['stepTwoResponse']    = '';
        $jsonResponse['stepThreeResponse']  = '';
        
        $jsonResponse['requestStep']        = '';
        
        if ($request->isXmlHttpRequest()) {
            $requestParams = $request->request->all();
            if(!empty($requestParams)) {

                if($step == 1) {
                     
                    $stepOneResponse = $this->paymentStepOne($request);
                    
                    $jsonResponse['stepOneResponse'] = $stepOneResponse;
                    $jsonResponse['requestStep'] = 1;
                }
                
                if($step == 2) {
                     
                    $stepOneResponse = $this->paymentStepOne($request);
                    $stepTwoResponse = $this->paymentStepTwo($request);
                    
                    if($stepOneResponse['status'] != 'success'){
                        
                        $jsonResponse['stepOneResponse'] = $stepOneResponse;
                        $jsonResponse['requestStep']     = 1;
                    }else{
                                                
                        $jsonResponse['stepTwoResponse'] = $stepTwoResponse;
                        $jsonResponse['requestStep']     = 2;
                    }
                }
                
                if($step == 3) {
                     
                    $stepOneResponse   = $this->paymentStepOne($request);
                    $stepTwoResponse   = $this->paymentStepTwo($request);
                    $stepThreeResponse = $this->paymentStepThree($request);
                    
                    if($stepOneResponse['status'] != 'success'){
                        
                        $jsonResponse['stepOneResponse'] = $stepOneResponse;
                        $jsonResponse['requestStep']     = 1;
                        
                    }else if($stepTwoResponse['status'] != 'success'){
                        
                        $jsonResponse['stepTwoResponse'] = $stepTwoResponse;
                        $jsonResponse['requestStep']     = 2;
                    }else{
                                                
                        $jsonResponse['stepThreeResponse'] = $stepThreeResponse;
                        $jsonResponse['requestStep']       = 3;
                    }
                }                 
            }
            else {

                $jsonResponse['status']  = 'failed';
                $jsonResponse['message'] = 'Invalid request.';
            }
        }else{

            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }

        echo json_encode($jsonResponse);
        exit;
    }
    
    public function paymentStepOne($request) {
    
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
        $jsonResponse['status'] = 'success';
    
        return $jsonResponse;
    }
    
    public function paymentStepTwo($request) {
    
        $em          = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id
    
        $requestParams = $request->request->all();
        $errorMsgArr = $this->paymentOptionAtrributesError();
        
        $jsonResponse = array();
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
    
        if($requestParams){
    
            if(isset($requestParams['Billing']) && !empty($requestParams['Billing'])){
                
                $billingData  = $requestParams['Billing'];

                foreach ($billingData as $key => $val){

                    if(array_key_exists($key,$errorMsgArr)){

                        if($val == ''){

                            $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                        }
                    }
                }

                if($jsonResponse['errorMessages']){

                    $jsonResponse['status']   = 'error';
                }else{
                    
                    $jsonResponse['status']  = 'success';
                    $this->get('session')->set('billingInfo',$billingData);
                }
            }else{
                $jsonResponse['status']  = 'failed';
                $jsonResponse['message'] = 'Please check billing information data.';
            }            
        }else{
    
            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }
    
        return $jsonResponse;
    }
    
    public function paymentStepThree(Request $request) {
    
        $em          = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id
    
        $requestParams = $request->request->all();
        $errorMsgArr = $this->paymentOptionAtrributesError();
        
        $creditCardData = '';
        $milstarData = '';
        
        $jsonResponse = array();
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
    
        if($requestParams){
    
            $processStep     = $requestParams['processStep'];
    
            if(in_array($requestParams['paymentBy'],$this->paymentOptions)){

                if($requestParams['paymentBy'] == 'cc'){

                    if(isset($requestParams['cc']) && !empty($requestParams['cc'])){
                        
                        $creditCardData  = $requestParams['cc'];
                        
                        foreach ($creditCardData as $key => $val){
    
                            if(array_key_exists($key,$errorMsgArr)){
    
                                if($val == ''){
    
                                    $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                                }
                            }
                        }
                    }

                    if(isset($requestParams['Billing']) && !empty($requestParams['Billing'])){
                        
                        $billingData  = $requestParams['Billing'];
                        
                        foreach ($billingData as $key => $val){
                        
                            if(array_key_exists($key,$errorMsgArr)){
                        
                                if($val == ''){
                        
                                    $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                                }
                            }
                        }
                    }
                }

                if($requestParams['paymentBy'] == 'milstar'){
                    
                    if(isset($requestParams['milstar']) && !empty($requestParams['milstar'])){
                        
                        $milstarData     = $requestParams['milstar'];
                        foreach ($milstarData as $key => $val){
    
                            if(array_key_exists($key,$errorMsgArr)){
    
                                if($val == ''){
    
                                    $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                                }
                            }
                        }
                    }                        
                }

                if($jsonResponse['errorMessages']){

                    $jsonResponse['status']   = 'error';
                }else{

                    $this->get('session')->set('milstarInfo',$milstarData);
                    $this->get('session')->set('cardInfo',$creditCardData);
                    $this->get('session')->set('paymentBy',$requestParams['paymentBy']);

                    $jsonResponse['paymentBy'] = $requestParams['paymentBy'];
                    $jsonResponse['status']  = 'success';
                }
            }else{

                $jsonResponse['status']  = 'failed';
                $jsonResponse['message'] = 'Please select valid payment request.';
            }
            
        }else{
            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }
    
        return $jsonResponse;
    }
    
    public function doPaymentProcessAction(){
        
        
        $em                          = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user                        = $this->get('security.context')->getToken()->getUser();
        $sessionId                   = $this->get('session')->get('sessionId'); //Get Session Id        
        $orderNumber                 = $this->get('PaymentProcess')->generateOrderNumber();
        $summaryData                 = $this->get('DashboardSummary')->getUserServiceSummary();
        $billingData                 = $this->get('session')->get('billingInfo');
        $isDeersAuthenticatedForIPTV = $this->get('DeersAuthentication')->isDeersAuthenticatedForIPTV();
        $insertIdBillingAddress = '';
        
        $paymentBy = $this->get('session')->get('paymentBy');
        
        $isSuccess = false;
        $paymentUrl = ''; 
        
        if(in_array($paymentBy,$this->paymentOptions)){
            
            if(!$billingData){
                
                $this->get('session')->getFlashBag()->add('failure', 'Billing address information not found.');
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
            }
            
            if($paymentBy == 'paypal'){

                $paymentUrl = $this->redirect($this->generateUrl('lost_paymentby_expresscheckout'));
            }elseif($paymentBy == 'cc'){
                
                
                $paymentUrl = $this->redirect($this->generateUrl('lost_paymentby_dodirect'));
            }elseif($paymentBy == 'milstar'){
                
                if($isDeersAuthenticatedForIPTV){
                    
                    $paymentUrl = $this->redirect($this->generateUrl('lost_paymentby_milstar'));
                }else{
                    
                    $this->get('session')->getFlashBag()->add('failure', 'DEERS authentication is required for Milstar payment. You can <a href="'.$this->generateUrl('lost_user_deers_auth').'">click this link</a> to login and clear DEERS authentication');
                    return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
                }                
            }else{
                
                throw $this->createNotFoundException('Invalid Page Request');
            }
            
            
            if($summaryData['CartAvailable']){
                
                $objPaymentMethod = $em->getRepository('LostServiceBundle:PaymentMethod')->find(array_search($paymentBy, $this->paymentOptions));
                
                //Save paypal response in PaypalExpressCheckOutCustomer table
                $objPurchaseOrder = new PurchaseOrder();
                $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                $objPurchaseOrder->setSessionId($sessionId);
                $objPurchaseOrder->setOrderNumber($orderNumber);
                $objPurchaseOrder->setUser($user);
                $objPurchaseOrder->setTotalAmount($summaryData['TotalCartAmount']);
                
                $em->persist($objPurchaseOrder);
                $em->flush();
                $insertIdPurchaseOrder = $objPurchaseOrder->getId();
                
                if($insertIdPurchaseOrder){
                    
                    if($objPaymentMethod->getCode() == 'CreditCard'){
                        
                        //Store BillingAddress
                        $objBillingAddress = new BillingAddress();
                        
                        $objBillingAddress->setPurchaseOrder($objPurchaseOrder);
                        $objBillingAddress->setFirstname($billingData['Firstname']);
                        $objBillingAddress->setLastname($billingData['Lastname']);
                        $objBillingAddress->setAddress($billingData['Address']);
                        $objBillingAddress->setCity($billingData['City']);
                        $objBillingAddress->setState($billingData['State']);
                        $objBillingAddress->setZip($billingData['Zipcode']);
                        $objBillingAddress->setCountry($billingData['Country']);
                        $objBillingAddress->setUser($user);
                        
                        $em->persist($objBillingAddress);
                        $em->flush();
                        $insertIdBillingAddress = $objBillingAddress->getId();
                    }
                    
                    $updateServicePurchase = $em->createQueryBuilder()->update('LostServiceBundle:ServicePurchase', 'sp')
                                            ->set('sp.purchaseOrder', $insertIdPurchaseOrder)
                                            ->where('sp.sessionId =:sessionId')
                                            ->setParameter('sessionId', $sessionId)
                                            ->andWhere('sp.paymentStatus =:paymentStatus')
                                            ->setParameter('paymentStatus', 'New')
                                            ->andWhere('sp.user =:user')
                                            ->setParameter('user', $user)
                                            ->getQuery()->execute();
                    
                    if($updateServicePurchase){
                        
                        $isSuccess = true;                        
                    }
                }
            }
            
            if($isSuccess){
                
                $isSelevisionUser = $this->get('selevisionService')->createNewUser();
                
                if($isSelevisionUser == 0) {
                    
                    $this->get('session')->getFlashBag()->add('failure', 'Something went to wrong in selevision API.');
                    return $this->redirect($this->generateUrl('lost_service_purchaseverification'));                
                }
                
                $this->get('session')->set('PurchaseOrderId',$insertIdPurchaseOrder);
                $this->get('session')->set('PurchaseOrderNumber',$orderNumber);
                $this->get('session')->set('BillingAddressId',$insertIdBillingAddress);
                
                //Add Activity Log
                $activityLog = array();
                $activityLog['user'] 	    = $user;
                $activityLog['activity']    = 'Order#'.$orderNumber;
                $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment In process.';
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                //Activity Log end here
                
                return $paymentUrl;
            }else{
                
                $this->get('session')->getFlashBag()->add('failure', 'Selected payment option not available. please try another payment option.');
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));                
            }            
        }else{
            
            throw $this->createNotFoundException('Invalid Page Request');
        }               
    }
        

    public function paymentOptionAtrributesError(){

        $errorMsg = array(
                'Firstname'   => 'Please enter first name.',
                'Lastname'    => 'Please enter last name.',
                'Address'     => 'Please enter address.',
                'City'        => 'Please enter city.',
                'State'       => 'Please enter state.',
                'Zipcode'     => 'Please enter zip code.',
                'Country'     => 'Please select country.',
                'CardType'    => 'Please select card type.',
                'CardNumber'  => 'Please enter card number.',
                'ExpMonth'    => 'Please select month.',
                'ExpYear'     => 'Please select year.',
                'Cvv'         => 'Please enter cvv.',
                'MCardNumber' => 'Please enter card number',
                'MZipcode'    => 'Please enter zip code'
        );

        return $errorMsg;
    }
}
