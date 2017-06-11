<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserActivityLogController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('audit_log_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view audit log list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $searchParams = $request->query->all();
        
        // set audit log admin user change password
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'User activity log search';
        
        if (isset($searchParams['historicalData']) && $searchParams['historicalData'] == 'on') {
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched ".  json_encode($searchParams['historicalData']);
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
           // $em = $this->getDoctrine()->getManager('secondary');
            $query = $em->getRepository('LostUserBundle:UserActivityLog')->getAllActivityLogs();
          //  echo $query ; exit;
        } else {

            $query = $em->getRepository('LostUserBundle:UserActivityLog')->getAllActivityLogs();
        }

        if (isset($searchParams)) {

            if (isset($searchParams['startDate']) && $searchParams['startDate'] != '') {

                $startDate = new \DateTime($searchParams['startDate']);
                $searchParams['startDate'] = $startDate;
            }
            if (isset($searchParams['endDate']) && $searchParams['endDate'] != '') {

                $endDate = new \DateTime($searchParams['endDate']);
                $searchParams['endDate'] = $endDate;
            }

            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched ".  json_encode($searchParams);
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $query = $em->getRepository('LostUserBundle:UserActivityLog')->getActivityLogSearch($query, $searchParams);
        }
        
        

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:UserActivityLog:index.html.twig', array(
                    'pagination' => $pagination
        ));
    }

}
