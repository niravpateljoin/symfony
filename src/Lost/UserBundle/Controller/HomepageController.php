<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HomepageController extends Controller
{
    public function indexAction()
    {
//         $serviceLocation = $this->get('UserLocationWiseService')->getUserLocationService();
//         echo "<pre>"; print_r($serviceLocation); exit;
        
//         $service = $this->get('ActivityLog')->saveActivityLog(array());
//         echo "<pre>"; print_r($service); exit;
        
        return $this->render('LostUserBundle:Homepage:index.html.twig');
    }
}
