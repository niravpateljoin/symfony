<?php

namespace Lost\UserBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; 

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Symfony\Component\Routing\Router;
use Lost\UserBundle\Entity\UserActivityLog;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class LogoutListener implements LogoutSuccessHandlerInterface
{
	/** @var \Symfony\Component\Security\Core\SecurityContext */
	private $securityContext;
	
	/** @var \Doctrine\ORM\EntityManager */
	private $em;
	
        private $router;
        
	/**
	 * Constructor
	 * 
	 * @param SecurityContext $securityContext
	 * @param Doctrine        $doctrine
	 */
	public function __construct(Router $router, SecurityContext $securityContext, Doctrine $doctrine)
	{ 
		$this->securityContext = $securityContext;
		$this->em              = $doctrine->getEntityManager();
                $this->router = $router;
                
	}
	
	/**
	 * 
	 * @param request $request
	 */
	public function onLogoutSuccess(Request $request)
        { 
            if( ! $this->securityContext->getToken()) {
                if(strpos($request->getPathInfo(), '/admin/') !== false) { 
                    $response = new RedirectResponse($this->router->generate('admin_login'));
                } else {
                    $response = new RedirectResponse($this->router->generate('fos_user_security_login'));
                }
                return $response;
            }
            $admin = $this->securityContext->getToken()->getUser();
            
            // Create object of ActivityLog
            $objActivityLog = new UserActivityLog();
            
            if (
                    $admin->hasRole('ROLE_SUPER_ADMIN')
                    || $admin->hasRole('ROLE_HELPDESK')
                    || $admin->hasRole('ROLE_CASHIER')
                    || $admin->hasRole('ROLE_MANAGER')

                ) {
                
                $admin->setIsloggedin(false);
                    
                $this->em->persist($admin);
                //$this->em->flush();
                
                /* START: add admin audit log for logout activity */
                $objActivityLog->setAdmin($admin);
                $objActivityLog->setDescription('Admin ' . $admin->getUsername() . ' has logged out.');
                /* END: add admin audit log for logout activity */
                
                // set routing for adminpanel
                $route = $this->router->generate('admin_login');
            } else {
                
                /* START: add user audit log for logout activity */
                $objActivityLog->setUser($admin);
                $objActivityLog->setDescription('User ' . $admin->getUsername() . ' has logged out.');
                /* END: add user audit log for logout activity */
                
                // set routing for frontend
                $route = $this->router->generate('fos_user_security_login');
            }
            
            /* START: add user audit log for logout activity */
            $objActivityLog->setActivity('Logout');
            $objActivityLog->setIp($request->server->get('REMOTE_ADDR'));
            $objActivityLog->setSessionId($request->getSession()->getId());
            $objActivityLog->setVisitedUrl($request->getUri());

            $this->em->persist($objActivityLog);
            $this->em->flush();
            /* END: add user audit log for logout activity */
            
            $response = new RedirectResponse($route);

            return $response;
	}
       
}


