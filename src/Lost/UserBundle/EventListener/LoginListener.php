<?php

namespace Lost\UserBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Lost\UserBundle\Entity\UserLoginLog;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\Country;
use Lost\UserBundle\Entity\User;
use \DateTime;
/**
 * Listener responsible to change the redirection at the end of the password resetting
 */

class LoginListener {

    /** @var \Symfony\Component\Security\Core\SecurityContext */
    private $securityContext;

    /** @var \Doctrine\ORM\EntityManager */
    private $em;
    
    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    private $session;
    
    private $router;
    
    protected $container;
    


    /**
     * Constructor
     * 
     * @param SecurityContext $securityContext
     * @param Doctrine        $doctrine
     */
    public function __construct(Router $router, SecurityContext $securityContext, Doctrine $doctrine, Session $session, $container) {
        $this->securityContext = $securityContext;
        $this->em = $doctrine->getEntityManager();
        $this->session = $session;
        $this->router = $router;
        $this->router = $router;
        $this->container = $container;
        
        $this->ActivityLog = $container->get('ActivityLog');
        $this->GeoLocation = $container->get('GeoLocation');
                
    }

    /**
     * Do the magic.
     * 
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event) {
        $request = $event->getRequest();
        
        //$response = new RedirectResponse($this->router->generate('fos_user_security_login'));
        $objActivityLog = new UserActivityLog();
        
        if(strpos($request->getPathInfo(), '/admin/') !== false) {
            
            if ($this->securityContext->isGranted('ROLE_ADMIN')) {
                
                $admin = $event->getAuthenticationToken()->getUser();
            
                $admin->setIsloggedin(true);
                $this->em->persist($admin);
                $this->em->flush();
                
                
                /* START: add admin audit log for login activity */
                $activityLog = array();
                $activityLog['admin'] 	 = $admin;
                $activityLog['activity'] = 'Admin Logged In';
                $activityLog['description'] = 'Admin '.$admin->getUsername().' has logged in.';
                $this->ActivityLog->saveActivityLog($activityLog);
                /* END: add admin audit log for login activity */                

                $response = new RedirectResponse($this->router->generate('lost_admin_dashboard'));
            } else {
                //$this->session->getFlashBag()->add('failure', 'You are not authrize to login.');
                $response = new RedirectResponse($this->router->generate('admin_login'));
            }
        } else { 
            if ($this->securityContext->isGranted('ROLE_ADMIN')) {
                //$this->session->getFlashBag()->add('failure', 'You are not authrize to login.');
                $response = new RedirectResponse($this->router->generate('fos_user_security_logout'));
                //die('admin from frontend');
            } else {
                $response = new RedirectResponse($this->router->generate('lost_user_account'));
                
                $user = $event->getAuthenticationToken()->getUser();
                
                $geoLocation = $this->GeoLocation->getIPAddress('all');
                
                if(isset($geoLocation) && array_key_exists('country_code',$geoLocation) && array_key_exists('ip',$geoLocation)){
                
                    $countryCode  = ucwords($geoLocation['country_code']);
                    $ipAddress    = $geoLocation['ip'];
                    
                    $country = $this->em->getRepository('LostUserBundle:Country')->findOneBy(array('isoCode' => $countryCode));
                    
                    $userLoginLog = new UserLoginLog();
                    $userLoginLog->setIpAddress($ipAddress);
                    $userLoginLog->setCreatedAt(new DateTime());
                    $userLoginLog->setCountry($country);
                    $userLoginLog->setUser($user);
                    
                    $this->em->persist($userLoginLog);
                    $this->em->flush();
                }
                
                /* START: add admin audit log for login activity */
                $activityLog = array();
                $activityLog['admin'] 	    = $user;
                $activityLog['activity']    = 'Customer Logged In';
                $activityLog['description'] = 'User '.$user->getUsername().' has logged in.';
                $this->ActivityLog->saveActivityLog($activityLog);
                /* END: add admin audit log for login activity */
                
                $this->session->set('UserCurrentCountry', $country->getId());
                $this->session->set('ipAddress', $ipAddress);
            }
        }
        
        return $response;
    }
}

?>
