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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use Lost\UserBundle\Entity\UserActivityLog;

/**
 * Controller managing the registration
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RegistrationController extends Controller
{
    public function registerAction(Request $request)
    {  
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.registration.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->createUser();
       
        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
            
            // adding ip address to database
            $ip_address = $this->get('GeoLocation')->getIPAddress('ip');
            //$ip_address = $this->container->get('request')->getClientIp();
            $user->setIpAddress($ip_address);
            $user->setIpAddressLong(ip2long($ip_address));
            $user->setEnabled(true);
            $user->setLocked(false);
            
            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('fos_user_registration_confirmed');
                $response = new RedirectResponse($url);
            }

            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            
            //Add Activity Log
            $activityLog = array();
            $activityLog['user']         = $user;
            $activityLog['activity']     = 'Registration';
            $activityLog['description']  = 'User '.$user->getUsername().' is registered';

            $this->get('ActivityLog')->saveActivityLog($activityLog);
            //End here
            
            return $response;
        }

        return $this->render('LostUserBundle:Registration:register.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $email = $this->get('session')->get('fos_user_send_confirmation_email/email');
        $this->get('session')->remove('fos_user_send_confirmation_email/email');
        $user = $this->get('fos_user.user_manager')->findUserByEmail($email);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with email "%s" does not exist', $email));
        }

        $message = 'You have registered successfully. An email has been sent to '.$email.'. It contains an activation link you must click to activate your account.';
        
        $this->get('session')->getFlashBag()->clear();
        $this->get('session')->getFlashBag()->add('success', $message);
        return $this->redirect($this->generateUrl('lost_user_profile'));
    }

    /**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction(Request $request, $token)
    {
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');
        
        // calculated the difference in hours to validate verification link
        $email_verification_date = $user->getEmailVerificationDate();
        $today = new DateTime();

        $diff = $today->diff($email_verification_date);
        $hours = $diff->h;
        $hours = $hours + ($diff->days * 24);
        
         //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user;
        $activityLog['activity']     = 'Email Confirmation';
        
        /* check whether the email verificatin link has expired or not. link is 
         * valid for 72 hours only
        */
        if ($hours > 72) {
            /* START: add user audit log for email confirmation */
            $em = $this->getDoctrine()->getManager();
            
            $activityLog['description'] = 'User '.$user->getUsername().' has tried to confirm email after 72 hours';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
        
            
            /* END: add user audit log for email confirmation */
            
            return $this->render('LostUserBundle:Registration:confirm.html.twig', array('user' => $user));
        } else {

            $event = new GetResponseUserEvent($user, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_CONFIRM, $event);
            $user->setConfirmationToken(null);
            $user->setIsEmailVerified(true);
            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('lost_user_verification_success');
                $response = new RedirectResponse($url);
            }

            //$dispatcher->dispatch(FOSUserEvents::REGISTRATION_CONFIRMED, new FilterUserResponseEvent($user, $request, $response));

            /* START: add user audit log for email confirmation */
            $em = $this->getDoctrine()->getManager();
           
            $activityLog['description'] = 'User '.$user->getUsername().' has confirmed email';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            /* END: add user audit log for email confirmation */

            return $response;
        }
        
    }

    /**
     * Tell the user his account is now confirmed
     */
    public function confirmedAction()
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        
        return $this->render('LostUserBundle:Registration:confirmed.html.twig', array(
            'user' => $user,
        ));
    }
    
    /**
     * Tell the user to resend the verification link
    */
    public function resendAction(Request $request, $token) {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }       

        $tokenGenerator = $this->get('fos_user.util.token_generator');
        $token = $tokenGenerator->generateToken();
        $user->setConfirmationToken($token);
        $user->setEmailVerificationDate(new DateTime());
        $userManager->updateUser($user);

        $body = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:resend_email_verification.html.twig', array('user' => $user, 'token' => $token));

        $resend_email_verification = \Swift_Message::newInstance()
                //->setSubject("Welcome ".$user->getUsername()." to Lost!")
                ->setSubject("Welcome ".$user->getName()." to Lost Portal!")
                ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                ->setTo($user->getEmail())
                ->setBody($body->getContent())
                ->setContentType('text/html');

        $this->container->get('mailer')->send($resend_email_verification);
        //$this->get('session')->getFlashBag()->add('success', sprintf('An email has been sent to "%s". It contains an activation link you must click to verify your email address.', $user->getEmail()));
        
        
        //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user;
        $activityLog['activity']     = 'Resend Activation Email';
        $activityLog['description'] = 'User '.$user->getUsername().' has resend activation email';
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        return $this->render('LostUserBundle:Registration:resend.html.twig', array(
                    'user' => $user,
        ));
    }
    
    public function emailVerificationSuccessAction() {
        // clear the session
        $this->get('session')->invalidate();
        return $this->render('LostUserBundle:Registration:verification_success.html.twig');
    }
}
