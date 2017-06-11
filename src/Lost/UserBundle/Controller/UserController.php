<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\UserMacAddress;
use Lost\ServiceBundle\Entity\ServicePurchase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lost\UserBundle\Form\Type\UserMacAddressFormType;

/**
 * 
 */
class UserController extends Controller {

    /**
     * This is the action that authenticates a customer against DEERS 
     */
    public function deersAuthAction() {

        $request = $this->getRequest();

        $apiurl = $this->container->getParameter('deers_api_url');
        $url = $this->container->getParameter('deers_current_site_url');

        if ($request->query->has('AAFES_auth') && $request->query->get('AAFES_auth') != null) {

            $apiurl = $apiurl . '&AAFES_auth=' . urlencode($request->query->get('AAFES_auth'));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiurl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response);

            if ($result == null or $result == '') {
                $result['error'] = 'Due to internal issue, we can not process your request yet. Please try again later OR contact webmaster.';
                exit;
            }

            // Create object of ActivityLog
            $objActivityLog = new UserActivityLog();

            // Create entity manager
            $em = $this->getDoctrine()->getManager();

            $user = $this->get('security.context')->getToken()->getUser();

            /* START: add user audit log for deers authentication */
            $activityLog = array(
                'user' => $user,
                'activity' => 'Deers authentication',
            );
            /* END: add user audit log for deers authentication */

            if ($result->result == 1) {
                $user->setIsDeersAuthenticated(1);
                $user->setIsLastLogin(1);
                $user->setDeersAuthenticatedAt(new DateTime());
                $user->setCid($result->cid);
                $em->persist($user);
                //$em->flush();

                $request->getSession()->set('deers_auth', 1);
                $request->getSession()->set('userService', 1);

                $this->get('session')->getFlashBag()->add('success', "Deers authentication done successfully!");

                // description for audit log activity
                $activityLog['description'] = 'User ' . $user->getUsername() . ' has completed successfully Deers authentication';
            } else {
                $this->get('session')->getFlashBag()->add('failure', "You are either not Exchange customer or the authorization token has expired.");

                // description for audit log activity
                $activityLog['description'] = 'User ' . $user->getUsername() . ' has failed Deers authentication';
            }

            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $em->flush();

            if ($request->getSession()->get('deersAuthRequire')) {
                return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
            } else {
                return $this->redirect($this->generateUrl('lost_user_profile'));
            }
        } else {

            return new RedirectResponse('http://www.shopmyexchange.com/signin-redirect?loc=' . $url);
        }
        exit;
    }

    public function refundAction(Request $request, $id) {

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        //$service = $em->getRepository('LostUserBundle:UserService')->getUserPurchaseHistory($id);
        $service = $em->getRepository('LostUserBundle:UserService')->find($id);

        if ($service) {

            if ($service->getUser() && $user && $service->getUser()->getId() == $user->getId()) {

                $emailAddress = $this->container->getParameter('refund_email_addresses');

                $objDiffrentDays = date_diff($service->getActivationDate(), $service->getExpiryDate());
                $totalDays = $objDiffrentDays->format("%a");
                $oneDayMoney = ($service->getPayableAmount() / $totalDays);

                $objDiffrentDays = date_diff(new \DateTime(), $service->getExpiryDate());
                $remainingDays = $objDiffrentDays->format("%a");
                $refundAmount = number_format(($remainingDays * $oneDayMoney), 2, ".", ".");


                if ($service->getPayableAmount() < $refundAmount) {

                    $this->get('session')->getFlashBag()->add('failure', "You can't refund amount.");
                    return $this->redirect($this->generateUrl('lost_purchase_history'));
                }

                $body = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:refund.html.twig', array('user' => $user, 'refundAmount' => $refundAmount, 'service' => $service));


                // #########START##########
                // check selevision api to check whether customer exist in system
                $wsParam['cuLogin'] = $user->getUsername();

                $selevisionService = $this->get('selevisionService');
                $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

                // if customer exists, update the details
                if ($wsResponse['status'] == 1) {

                    // call selevisions service to update account

                    $wsParam['cuLogin'] = $user->getUsername();
                    $wsParam['offer'] = $service->getPackageId();

                    // disable service for the account

                    $wsResponseCustomerOffer = $selevisionService->callWSAction('unsetCustomerOffer', $wsParam);

                    if (!empty($wsResponseCustomerOffer) && $wsResponseCustomerOffer['status'] == 1) {

                        // send email to admin for manual refund
                        foreach ($emailAddress as $key => $value) {

                            $refundEmail = \Swift_Message::newInstance()
                                    ->setSubject("Refund Amount")
                                    ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                                    ->setTo($value)
                                    ->setBody($body->getContent())
                                    ->setContentType('text/html');
                            $this->container->get('mailer')->send($refundEmail);
                        }

                        $service->setExpiryDate(new \Datetime());
                        $service->setRefund(1);
                        $service->setRefundAmount($refundAmount);
                        $em->persist($service);
                        $em->flush();

                        $this->get('session')->getFlashBag()->add('success', "Your service packgae amount refund successfully .");
                        return $this->redirect($this->generateUrl('lost_purchase_history'));
                    } else {

                        $this->get('session')->getFlashBag()->add('failure', "Service offer does not exits.");
                        return $this->redirect($this->generateUrl('lost_purchase_history'));
                    }
                } else {

                    $this->get('session')->getFlashBag()->add('failure', "You are not authorized to refund payment.");
                    return $this->redirect($this->generateUrl('lost_purchase_history'));
                }

                // #########END##########
            } else {

                $this->get('session')->getFlashBag()->add('failure', "Service is not found in your account.");
                return $this->redirect($this->generateUrl('lost_purchase_history'));
            }
        } else {

            $this->get('session')->getFlashBag()->add('failure', "Service is not found active in your account.");
            return $this->redirect($this->generateUrl('lost_purchase_history'));
        }
    }
    
    
    public function purchaseCreditAction(Request $request) {
        
        $sessionId = $this->get('paymentProcess')->generateCartSessionId();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $form = $this->createFormBuilder(array())
                    ->add('credit', 'entity', array(
                            'class' => 'LostAdminBundle:Credit',
                            'empty_value' => 'Credit',
                            'property' => 'amount'))
                    ->getForm();
        
        if ($request->getMethod() == "POST") {
        
            $form->handleRequest($request);
            
            if($form->isValid()){

                $data = $form->getData();
                
                $credit = $data['credit']->getCredit();
                $amount = $data['credit']->getAmount();
                
                $objServicePurchase = $em->getRepository('LostServiceBundle:ServicePurchase')->findOneBy(array('isCredit' => 1, 'paymentStatus' => 'New'));
                if(!$objServicePurchase){
                    
                    $objServicePurchase = new ServicePurchase();
                }
                                
                $objServicePurchase->setUser($user);
                $objServicePurchase->setCredit($data['credit']);
                $objServicePurchase->setActualAmount($amount);
                $objServicePurchase->setPayableAmount($amount);
                $objServicePurchase->setIsCredit(1);
                $objServicePurchase->setSessionId($sessionId);
                $objServicePurchase->setPackageId(0);
                $objServicePurchase->setPackageName('');
                $em->persist($objServicePurchase);
                $em->flush();
                
                if($objServicePurchase->getId()){
                    
                    return $this->redirect($this->generateUrl('lost_service_purchaseverification'));
                }                                
            }            
        }
        return $this->render('LostUserBundle:User:purchaseCredit.html.twig', array(
                    'form' => $form->createView(),
                    'user' => $user
                ));
    }    
}
