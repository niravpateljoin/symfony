<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DeersAuthenticationController extends Controller {
    protected $container;
    protected $em;
    protected $session;
    protected $securitycontext;
    protected $request;

    public function __construct($container) {

        $this->container = $container;
        
        $this->em = $container->get('doctrine')->getManager();
        $this->session = $container->get('session');
        $this->securitycontext = $container->get('security.context');
        $this->request = $container->get('request');
    }

    public function checkDeersAuth() {

        $User = $this->securitycontext->getToken()->getUser();
        
        $userLocationServices = $this->get('UserLocationWiseService')->getUserLocationService();
        
        $returnDeersAuth = array();
        
        if(! empty($userLocationServices)) {
            
            if(array_key_exists('IPTV', $userLocationServices)) {
                
                $userPurchaseItem = $this->em->getRepository('LostUserBundle:UserService')->getUsersPurchasedService($User, true);
                
                if($User->getIsDeersAuthenticated() != '1' && empty($userPurchaseItem)) {
                    
                    $returnDeersAuth['IPTV'] = 1;
                } elseif($User->getIsDeersAuthenticated() != '1' && ! empty($userPurchaseItem) && array_key_exists('IPTV', $userPurchaseItem) || array_key_exists('ISP', $userPurchaseItem)) {
                    
                    $returnDeersAuth['IPTV'] = 2;
                } elseif($User->getIsDeersAuthenticated() == '1' && ! empty($userPurchaseItem) && array_key_exists('IPTV', $userPurchaseItem)) {
                    
                    $objSetting = $this->em->getRepository('LostAdminBundle:Setting')->findOneByName('deers_timeframe');
                    $settingDays = $objSetting->getValue();
                    $objDays = date_diff($User->getDeersAuthenticatedAt(), new \DateTime());
                    $days = $objDays->days;
                    
                    if($settingDays < $days) {
                        
                        $User->setIsDeersAuthenticated(0);
                        $User->setIsLastLogin(0);
                        $this->em->persist($User);
                        $this->em->flush();
                        
                        $this->session->set('deers_auth', 0);
                        $returnDeersAuth['IPTV'] = 2;
                    }
                }
            }
        }
        
        return $returnDeersAuth;
    }
    
    public function isDeersAuthenticatedForIPTV() {
        
        $user = $this->securitycontext->getToken()->getUser();
        $userLocationServices = $this->get('UserLocationWiseService')->getUserLocationService();
        
        if(! empty($userLocationServices)) {
            
            if(array_key_exists('IPTV', $userLocationServices)) {
                
                if($user->getIsDeersAuthenticated() == 1){
                    
                    $objSetting     = $this->em->getRepository('LostAdminBundle:Setting')->findOneByName('deers_timeframe');
                    
                    if($objSetting){
                        
                        $settingDays    = $objSetting->getValue();
                        $objDays        = date_diff($user->getDeersAuthenticatedAt(), new \DateTime());
                        $days           = $objDays->days;
                        
                        if($settingDays < $days) {
                        
                            $user->setIsDeersAuthenticated(0);
                            $user->setIsLastLogin(0);
                            $this->em->persist($user);
                            $this->em->flush();
                        
                            $this->session->set('deers_auth', 0);
                        }else{
                            
                            return true;
                        }
                    }
                }
            }
        }                
        return false;
    }
}
