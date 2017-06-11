<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lost\UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \DateTime;
use Lost\UserBundle\Entity\UserActivityLog;

/**
 * Controller managing the resetting of the password
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class ResettingController extends Controller {

    /**
     * Request reset user password: show form
     */
    public function requestAction() {
        return $this->render('LostUserBundle:Resetting:request.html.twig');
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction(Request $request) {
        $username = $request->request->get('username');
        //resettype = [password, username]
        /** @var $user UserInterface */
        if ($request->request->get('resettype') == 'username') {
            $user = $this->get('fos_user.user_manager')->findUserByEmail($username);
        } else {
            $user = $this->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);
        }

        if (null === $user) {
            return $this->render('LostUserBundle:Resetting:request.html.twig', array(
                        'invalid_email' => $username
            ));
        }
        /*
          if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
          return $this->render('LostUserBundle:Resetting:passwordAlreadyRequested.html.twig');
          }
         * 
         */

        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        // check whether the link has been expired or not (72 hrs)
        $password_requested_date = $user->getPasswordRequestedAt();
        if ($password_requested_date) {
            $today = new DateTime();

            $diff = $today->diff($password_requested_date);
            $hours = $diff->h;
            $hours = $hours + ($diff->days * 24);

            /* check whether the email verificatin link has expired or not. link is 
             * valid for 72 hours only
             */
            if ($hours > 72) {
                /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
                $tokenGenerator = $this->get('fos_user.util.token_generator');
                $user->setConfirmationToken($tokenGenerator->generateToken());
            }
        }

        
        //Add Activity Log
        $activityLog = array();
        $activityLog['user'] = $user;
        
        if ($request->request->get('resettype') == 'username') {

            $body = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:forgot_username_email.html.twig', array('user' => $user));

            $forgotusername_email = \Swift_Message::newInstance()
                    //->setSubject("Welcome ".$user->getFirstname()." to Lost!")
                    ->setSubject("Welcome " . $user->getFirstname() . " to Lost!")
                    ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                    ->setTo($user->getEmail())
                    ->setBody($body->getContent())
                    ->setContentType('text/html');

            $this->container->get('mailer')->send($forgotusername_email);

            $activityLog['activity'] = 'Forgot Username Request';
            $activityLog['description'] = 'User ' . $user->getUsername() . ' has made request for forgot username';
        } else { // password
            $this->get('fos_user.mailer')->sendResettingEmailMessage($user);

            $activityLog['activity'] = 'Forgot Password Request';
            $activityLog['description'] = 'User ' . $user->getUsername() . ' has made request for forgot password';
        }
        //
        $user->setPasswordRequestedAt(new \DateTime());
        $this->get('fos_user.user_manager')->updateUser($user);
        
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        return new RedirectResponse($this->generateUrl('fos_user_resetting_check_email', array('email' => $this->getObfuscatedEmail($user))
        ));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction(Request $request) {
        $email = $request->query->get('email');

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('fos_user_resetting_request'));
        }

        $message = 'An email has been sent to ' . $email . '. It contains a link you must click to reset your password.';

        $this->get('session')->getFlashBag()->clear();
        $this->get('session')->getFlashBag()->add('success', $message);
        return $this->redirect($this->generateUrl('fos_user_resetting_request'));
        /*
          return $this->render('LostUserBundle:Resetting:checkEmail.html.twig', array(
          'email' => $email,
          )); */
    }

    /**
     * Reset user password
     */
    public function resetAction(Request $request, $token) {
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.resetting.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        // check whether the link has been expired or not (72 hrs)
        $password_requested_date = $user->getPasswordRequestedAt();
        $today = new DateTime();

        $diff = $today->diff($password_requested_date);
        $hours = $diff->h;
        $hours = $hours + ($diff->days * 24);

        /* check whether the email verificatin link has expired or not. link is 
         * valid for 72 hours only
         */
        if ($hours > 72) {

            /* START: add user audit log for reset password */
            $em = $this->getDoctrine()->getManager();
            
            $activityLog = array();
            $activityLog['user']  = $user;
            $activityLog['activity']     = 'Reset password';
            $activityLog['description'] = 'User ' . $user->getUsername() . ' has tried to reset password after 72 hours';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $this->get('session')->getFlashBag()->add('failure', "Your password reset link has been expired. please resend new link");
            return $this->render('LostUserBundle:Resetting:request.html.twig');
        }

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isValid()) {

            ##########START##########
            // check selevision api to check whether customer exist in system
            $wsParam = array();
            $wsParam['cuLogin'] = $user->getUsername();

            $selevisionService = $this->get('selevisionService');
            $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

            if ($wsResponse['status'] == 1) {
                // get plain password for selevisions
                $changePasswordArr = $request->request->get('fos_user_resetting_form');

                $currentPassword = $wsResponse['password'];
                $newPassword = $changePasswordArr['plainPassword']['first'];
            }
            ##########END##########

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('lost_password_reset_success');
                $response = new RedirectResponse($url);
            }

            /* START: add user audit log for reset password */
            
            $activityLog = array();
            $activityLog['user'] = $user;
            $activityLog['activity'] = 'Reset password';
            $activityLog['description'] = 'User ' . $user->getUsername() . ' has reset password successfully';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            /* END: add user audit log for Search admin */
            
            ##########START##########
            if ($wsResponse['status'] == 1 && $currentPassword != $newPassword) {

                // call selevisions service to update password
                $wsParam['cuPwd'] = $currentPassword;
                $wsParam['cuNewPwd1'] = $newPassword;
                $wsParam['cuNewPwd2'] = $newPassword;

                $wsResponse = $selevisionService->callWSAction('changeCustomerPwd', $wsParam);
            }
            ##########END##########

            //$dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
        }

        return $this->render('LostUserBundle:Resetting:reset.html.twig', array(
                    'token' => $token,
                    'form' => $form->createView(),
        ));
    }

    /**
     * Get the truncated email displayed when requesting the resetting.
     *
     * The default implementation only keeps the part following @ in the address.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(UserInterface $user) {
        $email = $user->getEmail();
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    public function resetSuccessAction() {
        // clear the session
        $this->get('session')->invalidate();
        return $this->render('LostUserBundle:Resetting:reset_success.html.twig');
    }

}
