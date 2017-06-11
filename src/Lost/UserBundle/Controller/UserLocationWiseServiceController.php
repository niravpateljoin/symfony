<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserLocationWiseServiceController extends Controller
{
    protected $container;
    
    protected $em;
    protected $session;
    protected $securitycontext;
    protected $request;
    
    public function __construct($container) {
    
        $this->container = $container;
    
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->request           = $container->get('request');
    }
    
    public function getUserLocationService() {
        
        if($this->session->has('UserLocationWiseService')) {
            return $this->session->get('UserLocationWiseService'); 
        }
        
        $clientDetails = $this->get('GeoLocation')->getIPAddress('all');
        
        $clientIp = $clientDetails['ip'];
        
        $user = $this->securitycontext->getToken()->getUser();
        
        $services = array();
        $services['IsMilstarEnabled'] = 0;
        $services['FacNumber'] = '';
        $isLocationServiceAvailable = 0;
        
        ################### START CODE FOR SERVICE LOCATION ###################
        $userIpAddressZone = $this->em->getRepository('LostAdminBundle:IpAddressZone')->getUserZone($clientIp);
        
        if($userIpAddressZone) {
            
            if($userIpAddressZone->getServices()){
                // get the services for IP range
                foreach($userIpAddressZone->getServices() as $service) {
                    $services[strtoupper($service->getName())]['ServiceId'] = $service->getId();
                    $services[strtoupper($service->getName())]['ServiceName'] = $service->getName();                              
                }
                
                $isLocationServiceAvailable = 1;
            }
            
            if(!empty($services)) {
                // check milstar facility for IP range
                if($userIpAddressZone->getIsMilstarEnabled() && array_key_exists('IPTV',$services)) {
                    $services['IsMilstarEnabled'] = 1;
                    $services['FacNumber'] = $userIpAddressZone->getMilstarFacNumber();
                }
            }
        }
        
        ################### END CODE FOR SERVICE LOCATION ###################
        
        ################### START CODE FOR COUNTRY SERVICE ###################
        // now what if service location is not exist for current user IP
        // then provide the services based on the user's country id
        
        if(!$isLocationServiceAvailable) {
            
            $services = array('IsMilstarEnabled' => 0);
            // get country based on user IP
            $country = $this->em->getRepository('LostUserBundle:Country')->findOneBy(array('isoCode' => $clientDetails['country_code']));
            
            if($country) {
                $countryServices = $this->em->getRepository('LostUserBundle:CountrywiseService')->getAllActiveCountrywiseService($country->getId());
                
                foreach ($countryServices as $key => $service) {
                    $services[strtoupper($service['name'])]['ServiceId']   = $service['id'];
                    $services[strtoupper($service['name'])]['ServiceName'] = $service['name'];            
                }
            }
        }
        ################### END CODE FOR COUNTRY SERVICE ###################
        
        ################### START CODE FOR DISABLED SERVICE ###################
        // now check if any service is disabled for user. If any, excempt that service
        $disabledServices = $this->em->getRepository('LostUserBundle:UserServiceSetting')->getDisableServices($user->getId());
        
        if(!empty($disabledServices)) {
            
            foreach($disabledServices as $disableService) {                
                if(array_key_exists($disableService, $services)) {
                    unset($services[$disableService]);
                }
            }
        }
        ################### END CODE FOR DISABLED SERVICE ###################
        
        ################### START CODE IF MAIN IS SERVICE DISABLED ###################
        $disabledServices = $this->em->getRepository('LostUserBundle:Service')->getDisableServices();
        
        if(!empty($disabledServices)) {
            foreach($disabledServices as $disableService) {                
                if(array_key_exists($disableService, $services)) {
                    unset($services[$disableService]);
                }
            }
        }
        ################### END CODE IF MAIN IS SERVICE DISABLED ###################

        // set services to session, so response will be quick
        $this->session->set('UserLocationWiseService', $services);
        
        //echo "<pre>"; print_r($services); exit;
      
        return $services;
        
    }
    
}
