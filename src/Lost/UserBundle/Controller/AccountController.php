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
use Lost\UserBundle\Form\Type\UserMacAddressFormType;


class AccountController extends Controller {

    public function indexAction(Request $request) {
        
        $user      = $this->get('security.context')->getToken()->getUser();
        $sessionId = $this->get('session')->get('sessionId');
        
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        
        $checkDeersAuthentication = $this->get('DeersAuthentication')->checkDeersAuth();
        
        return $this->render('LostUserBundle:Account:index.html.twig', 
                                            array(
                                                    'user' => $user,
                                                    'sessionId' => $sessionId,
                                                    'IPTV' => 'IPTV',
                                                    'ISP' => 'ISP',
                                                    'checkDeersAuthentication' => $checkDeersAuthentication,
                                                    'summaryData' => $summaryData
                                                 ));
    }
    
    public function accountUpdateAction(Request $request, $tab) {
        
        $user = $this->get('security.context')->getToken()->getUser();
               
        //$tab = 1;
        $email = $user->getEmail();
        $username = $user->getUsername();
        $wsParam = array();

        $em = $this->getDoctrine()->getManager();
        
        $form = $this->createForm(new AccountFormType(), $user);
        $changePasswordForm = $this->createForm(new ChangePasswordFormType(), $user);
        $accountSettingForm = $this->createForm(new AccountSettingFormType(), $user);
        

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            $changePasswordForm->handleRequest($request);
            $accountSettingForm->handleRequest($request);

            /* START: add user audit log for update profile */
            $activityLog = array();
            $activityLog['user'] = $user;
            /* END: add user audit log for update profile */

            if ($request->request->has('lost_user_account_update')) {

                $tab = 1;
                if ($form->isValid()) {

                    $activityLog['activity'] = 'Update User Account';

                    if ($form->getData()->getEmail() != $email) {

                        $userManager = $this->get('fos_user.user_manager');
                        $tokenGenerator = $this->get('fos_user.util.token_generator');
                        $token = $tokenGenerator->generateToken();
                        $user->setConfirmationToken($token);
                        $user->setIsEmailVerified(0);
                        $user->setEmailVerificationDate(new DateTime());

                        $userManager->updateUser($user);

                        $body = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:resend_email_verification.html.twig', array('user' => $user, 'token' => $token));

                        $resend_email_verification = \Swift_Message::newInstance()
                                ->setSubject("Welcome " . $user->getUsername() . " to Lost!")
                                ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                                ->setTo($user->getEmail())
                                ->setBody($body->getContent())
                                ->setContentType('text/html');

                        $this->container->get('mailer')->send($resend_email_verification);

                        // add description for audit log if email change
                        $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated account details. Changed email to ' . $user->getEmail();
                    } else {
                        // add description for audit log update profile
                        $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated account details.';
                    }

                    /* START: save record into user audit log for update profile */
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    /* END: save record into user audit log for update profile */

                    $user->setUsername($username);
                    $em->persist($user);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', "Account information updated successfully!");

                    ##########START##########
                    // check selevision api to check whether customer exist in system
                    $wsParam['cuLogin'] = $user->getUsername();

                    $selevisionService = $this->get('selevisionService');
                    $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

                    // if customer exists, update the details
                    if ($wsResponse['status'] == 1) {
                        
                        // get account details for selevisions account
                        $accountArr = $request->request->get('lost_user_account_update');

                        // call selevisions service to update account
                        $wsParam['cuNewFirstName'] = $accountArr['firstname'];
                        $wsParam['cuNewLastName']  = $accountArr['lastname'];
                        $wsParam['cuNewEmail']     = $user->getEmail();
                        
                        $wsResponse = $selevisionService->callWSAction('updateCustomer', $wsParam);
                    }
                    ##########END##########                    
                }
            }
            if ($request->request->has('lost_user_changepassword')) {

                $tab = 2;
                if ($changePasswordForm->isValid()) {

                    ##########START##########
                    // check selevision api to check whether customer exist in system
                    $wsParam = array();
                    $wsParam['cuLogin'] = $user->getUsername();

                    $selevisionService = $this->get('selevisionService');
                    $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);
                    
                    if ($wsResponse['status'] == 1) {
                        // get plain password for selevisions
                        $changePasswordArr = $request->request->get('lost_user_changepassword');

                        $currentPassword = $changePasswordArr['current_password'];
                        $newPassword = $changePasswordArr['plainPassword']['first'];
                        
                        $seleVisionCurrrentPwd = $wsResponse['password'];
                    }
                    ##########END##########

                    $userManager = $this->get('fos_user.user_manager');
                    $userManager->updateUser($user);
                    $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");

                    /* START: add user audit log for update profile */
                    $activityLog['activity'] = 'Change Password';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated password.';

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    /* END: add user audit log for update profile */

                    ##########START##########
                    if ($wsResponse['status'] == 1 && ($user->getNewSelevisionUser() || $currentPassword != $newPassword)) {
                        
                        // call selevisions service to update password
                        $wsParam['cuPwd'] = ($user->getNewSelevisionUser())?$seleVisionCurrrentPwd:$currentPassword;
                        $wsParam['cuNewPwd1'] = $newPassword;
                        $wsParam['cuNewPwd2'] = $newPassword;
                        
                        $wsResPwd = $selevisionService->callWSAction('changeCustomerPwd', $wsParam);
                        
                        if($wsResPwd['status'] == 1){
                            
                            $user->setNewSelevisionUser(0);
                            $em->persist($user);
                            $em->flush();
                        }                        
                    }
                    ##########END##########
                }
            }
            if ($request->request->has('lost_user_account_setting')) {

                $tab = 3;
                if ($accountSettingForm->isValid()) {

                    $userManager = $this->get('fos_user.user_manager');
                    $userManager->updateUser($user);
                    $this->get('session')->getFlashBag()->add('success', "Account settings updated successfully!");

                    /* START: add user audit log for update profile */
                    $activityLog['activity'] = 'Update Account Setting';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated account setting.';

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    /* END: add user audit log for update profile */
                }
            }
        }
        
        // get user mac address
        $objMacAddress = $em->getRepository("LostUserBundle:UserMacAddress")->findBy(array('user' => $user, 'isDelete' => 0));
        
        if(!$this->get('session')->has('maxMacAddress', 0)) {
            
            $userMacAddress = $em->getRepository('LostAdminBundle:Setting')->findOneBy(array('name' => 'mac_address'));
            $this->get('session')->set('maxMacAddress', $userMacAddress->getValue());
        }
        

        return $this->render('LostUserBundle:Account:accountUpdate.html.twig', array(
                    'form' => $form->createView(),
                    'changePasswordForm' => $changePasswordForm->createView(),
                    'accountSettingForm' => $accountSettingForm->createView(),
                    'tab' => $tab,
                    'userMacAddress' => $objMacAddress
        ));
    }

    public function typeAction(Request $request) {
        $user = $this->get('security.context')->getToken()->getUser();

        $form = $this->createForm(new AccountTypeFormType(), $user);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($request->request->has('lost_user_account_type_update')) {

                if ($form->isValid()) {
            
                    $userManager = $this->get('fos_user.user_manager');
                    
                    if ($form->getData()->getUserType() != 'US Military') {
                    
                        $userManager->updateUser($user);
                    } else {
                        
                        $userManager->updateUser($user);
                    }
                    
                    // Activity log user type
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'User type change';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has change type.';
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $this->get('session')->getFlashBag()->add('success', "Account type has been updated successfully!");
                }
            }
        }

        return $this->render('LostUserBundle:Account:type.html.twig', array('form' => $form->createView()));
    }
    
    public function ajaxAccountSummaryAction(Request $request, $tab)
    {
        $view = array();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        
        $view['summaryData'] = $summaryData;
        if(in_array($tab,array('1','2'))){
            
            if ($request->isXmlHttpRequest()) {
                                
                if($tab == 1){

                    return $this->render('LostUserBundle:Account:ajaxAccountSummaryTabOne.html.twig', $view);
                }
                
                if($tab == 2){
                    
                    return $this->render('LostUserBundle:Account:ajaxAccountSummaryTabTwo.html.twig', $view);
                }
            }
        }else{
            return 'Something went wrong.';
        }        
    }        
  
}
