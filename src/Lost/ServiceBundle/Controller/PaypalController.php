<?php

namespace Lost\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Lost\ServiceBundle\Model\PaymentDetails;
use Lost\ServiceBundle\Model\ExpressCheckout;
use Lost\ServiceBundle\Entity\PaypalCheckout;
use Lost\ServiceBundle\Entity\BillingAddress;
use Lost\UserBundle\Entity\UserService;
use Lost\UserBundle\Entity\UserServiceSetting;
use Lost\UserBundle\Entity\UserServiceSettingLog;

class PaypalController extends Controller
{
    
    public function dodirectProcessAction(){
        
        $em               = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user             = $this->get('security.context')->getToken()->getUser();
        $sessionId        = $this->get('session')->get('sessionId'); //Get Session Id
        $billingAddressId = $this->get('session')->get('BillingAddressId');
        $cardData         = $this->get('session')->get('cardInfo');
        $ipAddress        = $this->get('request')->server->get("REMOTE_ADDR");
        $purchaseOrderId  = $this->get('session')->get('PurchaseOrderId');
        $orderNumber       = $this->get('session')->get('PurchaseOrderNumber');
        
        $purchaseOrder     = $em->getRepository('LostServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        $billingAddress    = $em->getRepository('LostServiceBundle:BillingAddress')->find($billingAddressId);
        $purchaedCartItems = $em->getRepository('LostServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
        
        if($purchaedCartItems && $billingAddress && $cardData){
            
            $cardData['IPAddress'] = $ipAddress;
            
            //Set Configuration of paypal for call setExpressCheckOut Method
            $configPaypal = array(
                    'PAYMENTACTION'    => 'Sale',
                    'METHOD'           => 'DoDirectPayment'
            );
        
            $express = new ExpressCheckout($configPaypal,$this->container); //Express Checkout Object
            $paypalResponse = $express->DoDirectPayment($purchaedCartItems,$billingAddress,$cardData);
            
            if(strtoupper($paypalResponse["ACK"]) == "SUCCESS" || strtoupper($paypalResponse["ACK"]) == "SUCCESSWITHWARNING")
            {
                //Status update New => Completed
                $condition = array('user' => $user, 'paymentStatus' => 'New', 'purchaseOrder' => $purchaseOrder);
                $this->get('PaymentProcess')->updateServicePurchaseData($condition,array('paymentStatus' => 'Completed'));
                
                $transactionId = urldecode($paypalResponse["TRANSACTIONID"]);
                $amount        = urldecode($paypalResponse["AMT"]);
                $creditCardNo  = $this->get('PaymentProcess')->formatCreditCard($this->get('PaymentProcess')->maskCreditCard($cardData['CardNumber']));
                $expireDate    = $cardData['ExpMonth'].'-'.$cardData['ExpYear'];
                
                //Save paypal response in PaypalExpressCheckOutCustomer table
                $paypalCheckout = new PaypalCheckout();
                $paypalCheckout->setBuyerEmailAddress($user->getEmail());
                $paypalCheckout->setBuyerCountryCode($billingAddress->getCountry());
                $paypalCheckout->setPaypalTransactionId($transactionId);
                $paypalCheckout->setUser($user);
                $paypalCheckout->setPaypalProcessStatus('Authorization');
                $paypalCheckout->setTotalAmount($amount);
                $paypalCheckout->setCreditCardNo($creditCardNo);
                $paypalCheckout->setCcExpiredDate($expireDate);
                $paypalCheckout->setCreatedAt(new \DateTime(date('Y-m-d H:i:s')));
                
                $em->persist($paypalCheckout);
                $em->flush();
                $insertIdPaypalCheckOut = $paypalCheckout->getId();
                
                if($insertIdPaypalCheckOut){

                    $purchaseOrder->setPaypalCheckout($paypalCheckout);
                    $purchaseOrder->setPaymentStatus('Completed');                    
                    $em->persist($purchaseOrder);
                    $em->flush();
                    
                    //Add Activity Log
                    $activityLog = array();
                    $activityLog['user'] 	    = $user;
                    $activityLog['activity']    = 'Order#'.$orderNumber;
                    $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment has been completed';
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    //Activity Log end here
                }
                
                //Purchase credit
                $creditPaymentRefundStatus = $this->get('packageActivation')->addCreditInUserAccount();
                
                //Activate Purchase packages
                $paymentRefundStatus = $this->get('packageActivation')->activateServicePack();
                
                if($paymentRefundStatus || $creditPaymentRefundStatus){
                    
                    $isPaymentRefund = $this->get('paymentProcess')->refundPayment();                    
                }
                
                return $this->redirect($this->generateUrl('lost_service_purchase_order_confirm',array('ord' => $orderNumber)));                

                
            }else if(strtoupper($paypalResponse["ACK"]) == "FAILURE"){
                
                $errorCode    = urldecode($paypalResponse["L_ERRORCODE0"]); //Paypal Error Code
                $errorMessage = urldecode($paypalResponse["L_LONGMESSAGE0"]); //Paypal Error Message
                
                $this->get('session')->remove('cardInfo');
                $this->get('session')->getFlashBag()->add('notice', urldecode($errorMessage));
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
            }else{
                
                $this->get('session')->remove('cardInfo');
                $this->get('session')->getFlashBag()->add('notice', 'Payment server not responding. please try after some time.');
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
            }                                    
        }else{
            
            throw $this->createNotFoundException('Invalid Page Request');
        }          
    }
    
    /**
     * function paypalExpressCheckoutAction()
     * This function is used for paypal setExpressCheckOut Process
     */
    public function paypalExpressCheckoutAction() {

        $request    = $this->getRequest();
        $isSecure   = $request->isSecure() ? 'https://' : 'http://';
        $em         = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user       = $this->get('security.context')->getToken()->getUser();
        $sessionId  = $this->get('session')->get('sessionId'); //Get Session Id        
        $userId     = $user->getId(); // Get Current Login User Id
        $purchaseOrderId  = $this->get('session')->get('PurchaseOrderId');
        
        $itemArr    = array(); //Init item array
        
        $siteUrl    = $isSecure.$this->getRequest()->getHost().$this->getRequest()->getBaseUrl();

        $purchaseOrder = $em->getRepository('LostServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        
        //If service purchase data not found throw to 404
        if (!$purchaseOrder) {
        
            throw $this->createNotFoundException('Invalid Page Request');
        }
        
        //Retrieve user's purchased items
        $purchaedCartItems = $em->getRepository('LostServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
        
        if($purchaedCartItems){
            
            //Set Configuration of paypal for call setExpressCheckOut Method
            $configPaypal = array(
                    'RETURNURL'    => $siteUrl.$this->container->getParameter('paypal_confirm_url'), //Set Success Return Url
                    'CANCELURL'    => $siteUrl.$this->container->getParameter('paypal_cancel_url'), //Set Cancel Return Url
                    'PAYMENTACTION'=> 'Sale' //Express Checkout Payment Action
            );
            
            $express = new ExpressCheckout($configPaypal,$this->container); //Paypal Express Checkout object
            $httpParsedResponseAr = $express->setExpressCheckout($purchaedCartItems); //Call setExpressCheckOut Method of Paypal
            
            if($httpParsedResponseAr){
                
                // set flash messages
                $this->get('session')->getFlashBag()->add('notice', urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]));
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
            }
        }else{
            
            throw $this->createNotFoundException('Invalid Page Request');
        }       
    }

    /**
     * function paymentConfirmAction()
     * This function will call after success from paypal setExpressCheckOut
     */
    public function paymentConfirmAction()
    {
        
        $em                = $this->getDoctrine()->getManager();
        $user              = $this->get('security.context')->getToken()->getUser();
        $userId            = $user->getId(); // Get Current Login User Id
        $sessionId         = $this->get('session')->get('sessionId');
        $orderNumber       = $this->get('session')->get('PurchaseOrderNumber');
        $purchaseOrderId   = $this->get('session')->get('PurchaseOrderId');
        
        $purchaseOrder = $em->getRepository('LostServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        
        if($purchaseOrder){

            //Complete DoExpressCheckout Process
            $response = $this->completeDoExpressCheckoutPayment();
            
            if($response['status'] == 'success'){
                
                //Purchase credit
                $creditPaymentRefundStatus = $this->get('packageActivation')->addCreditInUserAccount();
                
                //Activate Purchase packages
                $paymentRefundStatus = $this->get('packageActivation')->activateServicePack();
                
                if($paymentRefundStatus || $creditPaymentRefundStatus){
                
                    $isPaymentRefund = $this->get('paymentProcess')->refundPayment();                                        
                }
                
                return $this->redirect($this->generateUrl('lost_service_purchase_order_confirm',array('ord' => $orderNumber)));
                
            }else if($response['status'] == 'failed'){

                $this->get('session')->getFlashBag()->add('notice', $response['message']);
                
                if($response['returnTo'] == 'purchaseverification'){

                    return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
                }
                
                if($response['returnTo'] == 'account'){

                    return $this->redirect($this->generateUrl('lost_user_account'));
                }
                
            }else{
                
                $this->get('session')->getFlashBag()->add('notice', 'Something went worng in paypal checkout. please try again.');
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));                
            }            
        }else{
            
            //Redirect To Recharge Failed
            $this->get('session')->getFlashBag()->add('notice', 'Something went worng in paypal checkout. please try again.');
            return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
        }               
    }
    
    public function completeDoExpressCheckoutPayment(){
    
        $em              = $this->getDoctrine()->getManager(); // Init Entity Manager
        $sessionId       = $this->get('session')->get('sessionId');     // Get Session Id
        $paypalExpressId = $this->get('session')->get('paypal[insertIdPaypalExpress]');
        $user            = $this->get('security.context')->getToken()->getUser();
        $userId          = $user->getId();          // Get Login User Id
        $token           = $this->getRequest()->get('token');  // Get Paypal TokenID
        $PayerID         = $this->getRequest()->get('PayerID');// Get Paypal PayerId
        $purchaseOrderId = $this->get('session')->get('PurchaseOrderId');
        
        $response = array();
        $response['status'] = '';
        $response['message'] = '';
        $response['returnTo'] = '';
        
        $purchaseOrder = $em->getRepository('LostServiceBundle:PurchaseOrder')->find($purchaseOrderId);
    
        //If Service Purchase data not found throw 404
        if (!$purchaseOrder) {
    
            throw $this->createNotFoundException('No recharge data found');
        }
        
        if($token && $PayerID){ //Check Paypal token and payerId
            
            //Update Paypal token in purchase order
            $purchaseOrder->setPaypalToken($token);
            $em->persist($purchaseOrder);
            $em->flush();
    
            $purchaedCartItems = $em->getRepository('LostServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
    
            if($purchaedCartItems){

                //Set Configuration of paypal for call setExpressCheckOut Method
                $configPaypal = array(
                        'PAYMENTACTION'    => 'Sale',
                        'METHOD'           => 'DoExpressCheckoutPayment',
                        'TOKEN'            => $token,
                        'PAYERID'          => $PayerID,
                );

                $express = new ExpressCheckout($configPaypal,$this->container); //Express Checkout Object
                $httpParsedResponseAr = $express->DoExpressCheckoutPayment($purchaedCartItems); //Call DoExpressCheckout menthod

                if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
                {
                    if($purchaseOrder->getServicePurchases()){
                        
                        foreach ($purchaseOrder->getServicePurchases() as $servicePurchase){
                            
                            $servicePurchase->setPaymentStatus('Completed');
                            $em->persist($servicePurchase);
                            $em->flush();                                                        
                        }
                        
                        //Call Paypal GetExpressCheckOut Method
                        $express->setMethod('GetExpressCheckoutDetails');
                        $getExpressCheckOutDetail = $express->DoExpressCheckoutPayment($purchaedCartItems);
                        
                        //PayPal Responses Data
                        $transactionId  = $httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"];
                        $amount         = urldecode($httpParsedResponseAr["PAYMENTINFO_0_AMT"]);
                        $email          = urldecode($getExpressCheckOutDetail["EMAIL"]);
                        $payerStatus    = urldecode($getExpressCheckOutDetail["PAYERSTATUS"]);
                        $countryCode    = urldecode($getExpressCheckOutDetail["COUNTRYCODE"]);
                        $paymentACK     = urldecode($httpParsedResponseAr["PAYMENTINFO_0_ACK"]);
                        
                        //Save paypal response in PaypalExpressCheckOutCustomer table
                        $paypalCheckout = new PaypalCheckout();
                        $paypalCheckout->setBuyerEmailAddress($email);
                        $paypalCheckout->setPaypalPayerId($PayerID);
                        $paypalCheckout->setPaypalPayerStatus($payerStatus);
                        $paypalCheckout->setBuyerCountryCode($countryCode);
                        $paypalCheckout->setPaypalTransactionId($transactionId);
                        $paypalCheckout->setUser($user);
                        $paypalCheckout->setPaypalProcessStatus('DoExpressCheckOut');
                        $paypalCheckout->setTotalAmount($amount);
                        
                        $mysqldate = new \DateTime(date('Y-m-d H:i:s'));
                        $paypalCheckout->setCreatedAt($mysqldate);
                        
                        $em->persist($paypalCheckout);
                        $em->flush();
                        $insertIdPaypalExpress = $paypalCheckout->getId();

                        if($insertIdPaypalExpress){
                            
                            $purchaseOrder->setPaypalCheckout($paypalCheckout);
                            $purchaseOrder->setPaymentStatus('Completed');
                            $em->persist($purchaseOrder);
                            $em->flush();
                            
                            //Add Activity Log
                            $activityLog = array();
                            $activityLog['user'] 	    = $user;
                            $activityLog['activity']    = 'Order#'.$purchaseOrder->getOrderNumber();
                            $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$purchaseOrder->getOrderNumber().' payment has been completed';
                            $this->get('ActivityLog')->saveActivityLog($activityLog);
                            //Activity Log end here
                            
                            $response['status'] = 'success';                            
                        }else{
                            
                            $response['status'] = 'failed';
                            $response['message'] = 'Something went worng in paypal checkout. please try again.';
                            $response['returnTo'] = 'purchaseverification';
                        }
                    }                                        
                }else{
                    
                    $response['status'] = 'failed';
                    $response['message'] = urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]);
                    $response['returnTo'] = 'purchaseverification';                                        
                }
            }else{
            
                $response['status'] = 'failed';
                $response['message'] = 'Purchase service not found in cart.';
                $response['returnTo'] = 'account';
                
            }            
        }else{
            
            $response['status'] = 'failed';
            $response['message'] = 'Paypal service not available. please try another payment options';
            $response['returnTo'] = 'purchaseverification';
        }         

        return $response;
    }        
}
