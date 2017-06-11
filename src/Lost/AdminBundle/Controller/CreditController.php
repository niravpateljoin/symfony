<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\AdminBundle\Entity\Credit;
use Lost\UserBundle\Entity\User;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Form\Type\CreditFormType;
use Lost\UserBundle\Form\Type\CreditSearchFormType;


class CreditController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('credit_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view Credit list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('LostAdminBundle:Credit')->getAllCredit();
     
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:Credit:index.html.twig', array(
                'pagination' => $pagination,              
        ));

    }
    
     //Added For Grid List
     public function creditListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    
        $creditColumns = array('Id','Credit','Amount');
        $admin = $this->get('security.context')->getToken()->getUser();  
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($creditColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'uc.id';
            $sortOrder = 'ASC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'uc.id';
            }

            if ($gridData['order_by'] == 'Credit') {
                
                $orderBy = 'uc.credit';
            }
            
               if ($gridData['order_by'] == 'Amount') {
                
                $orderBy = 'uc.amount';
            }
           
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('LostAdminBundle:Credit')->getCreditGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        if (isset($data) && !empty($data)) {
            
            if (isset($data['result']) && !empty($data['result'])) {
                
                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );
                
                
                foreach ($data['result'] AS $resultRow) {
                   
                   
                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getId();                   
                    $row[] = $resultRow->getCredit();
                    $row[] = $resultRow->getAmount();
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    
                    $output['aaData'][] = $row;
                }
                
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function newAction(Request $request)
    {
        //   echo $this->get('admin_permission')->checkPermission('message_service_create') ;exit;
        if(! $this->get('admin_permission')->checkPermission('credit_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add Credit.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objCreditService = new Credit();

        $form = $this->createForm(new CreditFormType(),$objCreditService);

        if ($request->getMethod() == "POST")
        {
            $form->handleRequest($request);
            
            if ($form->isValid()) {

                $em->persist($objCreditService);
                $em->flush();
                
                // set audit log
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Credit';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added credit successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
        
                $this->get('session')->getFlashBag()->add('success', 'Credit added successfully.');
                return $this->redirect($this->generateUrl('lost_admin_credit_list'));
            }
        }

        return $this->render('LostAdminBundle:Credit:new.html.twig', array(
                'form' => $form->createView(),
        ));

    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('credit_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update Credit.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objCredit = $em->getRepository('LostAdminBundle:Credit')->find($id);

        if (!$objCredit) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find Credit.");
            return $this->redirect($this->generateUrl('lost_admin_credit_list'));
        }

        $form = $this->createForm(new CreditFormType(), $objCredit);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $em->persist($objCredit);
                $em->flush();
                
                // set audit log
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Credit';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated credit successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Credit updated successfully.');
                return $this->redirect($this->generateUrl('lost_admin_credit_list'));
            }
        }

        return $this->render('LostAdminBundle:Credit:edit.html.twig', array(
                'form' => $form->createView(),
                'usercredit' => $objCredit
        ));
    }

    public function deleteAction(Request $request) {
         $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('credit_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete Credit.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $activityLog = array();
        $activityLog['admin']       = $admin;
        $activityLog['activity']    = 'Delete Credit';
        $objCredit = $em->getRepository('LostAdminBundle:Credit')->find($id);

        if ($objCredit) {
            
            // set audit log
          
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted credit successfully";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objCredit);
            $em->flush();
            
            $result = array('type' => 'success', 'message' => 'Credit deleted successfully.');
        } else {
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete Credit ".$id;
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete Credit!');
        }
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }
}