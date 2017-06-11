<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DashboardController extends Controller
{
    public function indexAction()
    {
        
//         echo $this->get('admin_permission')->checkPermission('admin_list');
        return $this->render('LostAdminBundle:Dashboard:index.html.twig');
    }
}
