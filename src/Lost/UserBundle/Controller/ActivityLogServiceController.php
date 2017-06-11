<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Lost\UserBundle\Entity\UserActivityLog;

class ActivityLogServiceController extends Controller
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
    
    public function saveActivityLog($data)
    {

        $objActivityLog = new UserActivityLog();
        
        if (isset($data['admin']) && !empty($data['admin'])) {
        
            $objActivityLog->setAdmin($data['admin']);
        }
        
        if (isset($data['user']) && !empty($data['user'])) {
        
            $objActivityLog->setUser($data['user']);
        }
        
        $ipAddress = $this->get('session')->get('ipAddress');
        
        if(!$ipAddress) {
            
            $ipAddress = $this->get('GeoLocation')->getIPAddress('ip');
        }
        
        if(isset($data['activity']) && isset($data['description']) && $ipAddress) {
            $objActivityLog->setActivity($data['activity']);
            $objActivityLog->setDescription($data['description']);
            $objActivityLog->setIp($ipAddress);
            $objActivityLog->setSessionId($this->get('session')->getId());
            $objActivityLog->setVisitedUrl($this->request->getUri());
            
            $this->em->persist($objActivityLog);
            $this->em->flush();
        }
    }
}
