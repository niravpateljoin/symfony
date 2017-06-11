<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\UserBundle\Entity\SupportLocation;
use Lost\AdminBundle\Form\Type\SupportLocationFormType;
use Lost\UserBundle\Entity\UserActivityLog;

class SupportLocationController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_location_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view support location list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('LostUserBundle:SupportLocation')->getAllSupportLocation();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);
        
        return $this->render('LostAdminBundle:SupportLocation:index.html.twig', array(
                    'pagination' => $pagination
        ));
    }
    
    // support location grid list
    public function supportLocationListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $supportLocationColumns = array('Id','Location','Code');
        $admin = $this->get('security.context')->getToken()->getUser();
        
        // get common function for search data
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($supportLocationColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'c.id';
            $sortOrder = 'ASC';
            
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'c.id';
            }

            if ($gridData['order_by'] == 'Location') {
                
                $orderBy = 'c.name';
            }
            
               if ($gridData['order_by'] == 'Code') {
                
                $orderBy = 'c.code';
            }           
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('LostUserBundle:SupportLocation')->getSupportLocationGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
                    $row[] = $resultRow->getName();
                    $row[] = $resultRow->getCode();
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    
                    $output['aaData'][] = $row;
                    
                }
                
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    public function newAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_location_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add support location.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objLocation = new SupportLocation();
        $form = $this->createForm(new SupportLocationFormType(), $objLocation);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {

                $objLocation = $form->getData();

                $em->persist($objLocation);
                $em->flush();
                
                // set audit log add support location
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add support location';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added support location " . $objLocation->getName()." AND location short code ". $objLocation->getCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Support location added successfully.');
                return $this->redirect($this->generateUrl('lost_admin_support_location_list'));
            }
        }
        return $this->render('LostAdminBundle:SupportLocation:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_location_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update support location.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objLocation = $em->getRepository('LostUserBundle:SupportLocation')->find($id);

        if (!$objLocation) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find support location.");
            return $this->redirect($this->generateUrl('lost_admin_support_location_list'));
        }

        $form = $this->createForm(new SupportLocationFormType(), $objLocation);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objLocation = $form->getData();
                $em->persist($objLocation);
                $em->flush();
                
                // set audit log add support location
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit support location';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated support location " . $objLocation->getName()." and location short code ". $objLocation->getCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Support location updated successfully.');
                return $this->redirect($this->generateUrl('lost_admin_support_location_list'));
            }
        }

        return $this->render('LostAdminBundle:SupportLocation:edit.html.twig', array(
                    'form' => $form->createView(),
                    'location' => $objLocation
        ));
    }
    
    public function deleteAction(Request $request) {
        
        $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_location_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete support location.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $id = $request->get('id');
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();

        $objLocation = $em->getRepository('LostUserBundle:SupportLocation')->find($id);

        if ($objLocation) {
            
            // set audit log delete support location
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Delete support location';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted support location " . $objLocation->getName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->remove($objLocation);
            $em->flush();
            $result = array('type' => 'success', 'message' => 'Support location deleted successfully.');
            
        } else {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete support location.');
          
        }
        
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }

}
