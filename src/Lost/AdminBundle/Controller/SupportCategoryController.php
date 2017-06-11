<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\UserBundle\Entity\SupportCategory;
use Lost\AdminBundle\Form\Type\SupportCategoryFormType;
use Lost\UserBundle\Entity\UserActivityLog;

class SupportCategoryController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_category_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view support category list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository('LostUserBundle:SupportCategory')->getAllSupportCategory();     
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:SupportCategory:index.html.twig', array(
                    'pagination' => $pagination
        ));
    }
    
    public function supportCategoryListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    
        $supportCategoryColumns = array('Id','Name');
        $admin = $this->get('security.context')->getToken()->getUser();  
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($supportCategoryColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'c.id';
            $sortOrder = 'ASC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'c.id';
            }

            if ($gridData['order_by'] == 'Name') {
                
                $orderBy = 'c.name';
            }
           
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('LostUserBundle:SupportCategory')->getSupportCategoryGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
        if(! $this->get('admin_permission')->checkPermission('support_category_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add support category.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objCategory = new SupportCategory();
        $form = $this->createForm(new SupportCategoryFormType(), $objCategory);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {

                $objCategory = $form->getData();

                $em->persist($objCategory);
                $em->flush();
                
                // set audit log search group
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add support category';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added support category " . $objCategory->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                 
                $this->get('session')->getFlashBag()->add('success', 'You have successfully added support category.');
                return $this->redirect($this->generateUrl('lost_admin_support_category_list'));
            }
        }
        return $this->render('LostAdminBundle:SupportCategory:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_category_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update support category.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objCategory = $em->getRepository('LostUserBundle:SupportCategory')->find($id);

        if (!$objCategory) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find support category.");
            return $this->redirect($this->generateUrl('lost_admin_support_category_list'));
        }

        $form = $this->createForm(new SupportCategoryFormType(), $objCategory);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objCategory = $form->getData();

                $em->persist($objCategory);
                $em->flush();
                
                // set audit log edit support categoey
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit support category';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated support category " . $objCategory->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'You have successfully updated support category.');
                return $this->redirect($this->generateUrl('lost_admin_support_category_list'));
            }
        }

        return $this->render('LostAdminBundle:SupportCategory:edit.html.twig', array(
                    'form' => $form->createView(),
                    'category' => $objCategory
        ));
    }
    
    public function deleteAction(Request $request) {
        $id = $request->get('id'); 
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_category_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete support category.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete support category';

        $objCategory = $em->getRepository('LostUserBundle:SupportCategory')->find($id);

        if ($objCategory) {
            
            // set audit log delete support categoey
           
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted support category " . $objCategory->getName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->remove($objCategory);
            $em->flush();
            $result = array('type' => 'success', 'message' => 'Support category deleted successfully!');
           
        } else {
             $result = array('type' => 'danger', 'message' => 'Unable to find support category.');
        }
       
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }

}
