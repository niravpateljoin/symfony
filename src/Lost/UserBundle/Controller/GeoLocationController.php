<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GeoLocationController extends Controller
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
    
    public function getIPAddress($type = 'all')
    {
        $url = 'http://www.telize.com/geoip';
        
        if($type == 'ip' && $this->session->get('ipAddress') != ''){
            return $this->session->get('ipAddress');
        }
        
        if($type == 'all' && $this->session->get('all') != ''){
            return $this->session->get('all');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code after exec
        curl_close($ch);
        
        $result = json_decode($response,true);
        
        if($type != '' || $result){
            
            if($type == 'all'){
                $this->session->set('all',$result);
                return $result;
            }else{
                
                if(isset($result[$type]) && !empty($result[$type])){
                    
                    if($type == 'ip'){
                        
                        $this->session->set('ipAddress',$result[$type]);
                    }
                    
                    return $result[$type];
                }                
            }
        }else{
            
            $this->session->set('ipAddress',$this->request->getClientIp());
            return $this->request->getClientIp();
        }

        return false;
    }
}
