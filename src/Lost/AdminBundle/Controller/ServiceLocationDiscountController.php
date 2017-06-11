<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\AdminBundle\Form\Type\ServicesLocationDiscountFormType;
use Lost\AdminBundle\Form\Type\ServiceLocationFormType;
use Lost\AdminBundle\Entity\ServiceLocation;
use Lost\AdminBundle\Entity\ServiceLocationDiscount;
use Lost\UserBundle\Entity\UserActivityLog;

class ServiceLocationDiscountController extends Controller {

    /**
     * List Services in Admin panel
     */
    public function indexAction(Request $request) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view service location bundle discount list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('LostAdminBundle:ServiceLocation')->getAllServices();

        $searchParams = $request->query->all();

        if (isset($searchParams['searchTxt']) && !empty($searchParams['searchTxt'])) {
            
            // set audit log search service location discount
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Search service location discount';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched " . $searchParams['searchTxt'];
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $query = $em->getRepository('LostUserBundle:Service')->getAllServicesSearch($query, $searchParams['searchTxt']);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);
        return $this->render('LostAdminBundle:ServiceLocationDiscount:index.html.twig', array('pagination' => $pagination));
    }

    public function newAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add service location bundle discount.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objServiceLocation = new ServiceLocation();
        $objServiceLocationDiscount = new ServiceLocationDiscount();
        
        $objServiceLocation->addServiceLocationDiscount($objServiceLocationDiscount);
        
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new ServicesLocationDiscountFormType(), $objServiceLocation);
        
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                
                $objServiceLocation = $form->getData();
                $serviceLocations = $form->get('serviceLocation')->getData();
                $discountArr = array();
                if($objServiceLocation->getServiceLocationDiscounts()) {
                    foreach ($objServiceLocation->getServiceLocationDiscounts() as $key => $serviceLocationDiscount) {
                        $discountArr[$key]['min_amount'] = $serviceLocationDiscount->getMinAmount();
                        $discountArr[$key]['max_amount'] = $serviceLocationDiscount->getMaxAmount();
                        $discountArr[$key]['percentage'] = $serviceLocationDiscount->getPercentage();
                    }
                }
                
                if(!empty($discountArr) && !empty($serviceLocations)) {
                    
                    foreach($discountArr as $discount) {
                    
                        foreach($serviceLocations as $serviceLocation) {
                        
                            $objDiscount = new ServiceLocationDiscount();
                            $objDiscount->setServiceLocation($serviceLocation);
                            $objDiscount->setMinAmount($discount['min_amount']);
                            $objDiscount->setMaxAmount($discount['max_amount']);
                            $objDiscount->setPercentage($discount['percentage']);
                            $em->persist($objDiscount);
                        }
                    }
                }

                $em->flush();
                
                // set audit log add service location discount
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add service location discount';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new discount of service locations bundle successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Service location bundle discount added successfully!");
                return $this->redirect($this->generateUrl('lost_admin_ip_zone_list'));
            }
        }
        return $this->render('LostAdminBundle:ServiceLocationDiscount:new.html.twig', array('form' => $form->createView()));        
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update service location bundle discount.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objServiceLocation = $em->getRepository('LostAdminBundle:ServiceLocation')->find($id);

        if (count($objServiceLocation->getServiceLocationDiscounts()) <= 0) {
            $this->get('session')->getFlashBag()->add('failure', "Service location bundle discount does not exist.");
            return $this->redirect($this->generateUrl('lost_admin_ip_zone_edit'));
        }
        
        $form = $this->createForm(new ServicesLocationDiscountFormType(), $objServiceLocation);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                
                $objServiceLocation = $form->getData();
                $serviceLocation = $form->get('serviceLocation')->getData();
                
                if($objServiceLocation->getServiceLocationDiscounts()){
                
                    $discountUpdatedId = array();
                    
                    foreach ($objServiceLocation->getServiceLocationDiscounts() as $serviceLocationDiscount){
                        
                        $serviceLocationDiscount->setServiceLocation($serviceLocation);
                        $em->persist($serviceLocationDiscount);
                        $em->flush();
                                        
                        $discountUpdatedId[] = $serviceLocationDiscount->getId();
                    }
                    
                    $deleteDiscountList = $em->getRepository('LostAdminBundle:ServiceLocationDiscount')->getRemoveDiscountList($id,$discountUpdatedId);
                
                    if($deleteDiscountList){
                
                        foreach ($deleteDiscountList as $deleteDiscount){
                
                            $em->remove($deleteDiscount);
                        }
                    }
                }
                
                // set audit log edit service location discount
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit service location discount';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated discount of service location bundle ". $serviceLocation->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Service location bundle discount updated successfully!");
                return $this->redirect($this->generateUrl('lost_admin_ip_zone_list'));
            }
        }

        return $this->render('LostAdminBundle:ServiceLocationDiscount:edit.html.twig', array('form' => $form->createView(), 'id' => $id));        
    }

    public function deleteAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete service location bundle discount.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        
        // set audit log delete service location discount
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete service location discount';
        
        $objServices = $em->getRepository('LostUserBundle:Service')->find($id);

        if ($objServices) {
            $serviceName = $objServices->getName();
            $em->remove($objServices);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Service deleted successfully!");
            $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted service ". $serviceName;
            

        } else {

            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete service ";
            $this->get('session')->getFlashBag()->add('failure', "Service does not exist.");

        }
        
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        return $this->redirect($this->generateUrl('lost_admin_service_list'));
    }
}
