<?php

namespace Lost\UserBundle\EventListener;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface {

    protected $router;
    protected $security;
    protected $session;
    private $em;

    public function __construct(Router $router, SecurityContext $security, Session $session, Doctrine $doctrine) {
        $this->router = $router;
        $this->security = $security;
        $this->session = $session;
        $this->em = $doctrine->getEntityManager();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token) {
        
        
        if ($this->security->isGranted('ROLE_ADMIN')) {
        
            $this->session->getFlashBag()->add('danger', 'Invalid credentials.');
            $response = new RedirectResponse($this->router->generate('fos_user_security_logout'));
            
            
        } else {
            
            $user = $this->security->getToken()->getUser();
//            if($user) {
//                $user->getIsLastLogin() ? $this->session->set('deers_auth', '1') : $this->session->set('deers_auth', '0');
//            }
            $userService = $this->em->getRepository('LostUserBundle:UserService')->findOneBy(array('user' => $user));
            $this->session->set('userService', $userService ? '1' : '0');
            
            $userMacAddress = $this->em->getRepository('LostUserBundle:UserSetting')->findOneBy(array('user' => $user));
            
            if($userMacAddress) {
                
                $this->session->set('maxMacAddress', $userMacAddress->getMacAddress());
            }
            else {
                
                $userMacAddress = $this->em->getRepository('LostAdminBundle:Setting')->findOneBy(array('name' => 'mac_address'));
                
                $this->session->set('maxMacAddress', $userMacAddress->getValue());
            }
            
            
            $response = new RedirectResponse($this->router->generate('lost_user_account'));
        }

        return $response;
    }

}
