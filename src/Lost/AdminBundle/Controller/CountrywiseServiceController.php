<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\CountrywiseService;
use Lost\UserBundle\Entity\Country;
use Lost\AdminBundle\Form\Type\CountrywiseServiceFormType;
use \DateTime;

class CountrywiseServiceController extends Controller {

    public function indexAction(Request $request) {
        
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('country_service_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view country wise service list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();

        $query = $em->getRepository('LostUserBundle:Country')->getAllCountrywiseService($admin);

        $searchParams = $request->query->all();

        if (isset($searchParams['searchTxt']) && !empty($searchParams['searchTxt'])) {
            
                // add user audit log for Search service-list
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Search country wise service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " searched " . $searchParams['searchTxt'];

                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $query = $em->getRepository('LostUserBundle:Country')->getAllCountrywiseServiceSearch($query, $searchParams['searchTxt']);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:CountrywiseService:index.html.twig', array('pagination' => $pagination));
    }

    public function newAction(Request $request) {
        
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('country_service_create')) {        
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add country wise service.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $errorArr = array();
        
        $objCountrywiseService = new CountrywiseService();
        
        $form = $this->createForm(new CountrywiseServiceFormType(), $objCountrywiseService);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            $objService = $form->getData();
            
            $countryId = $objService->getCountry()->getId();
            foreach ($objService->getServices() as $service) {
                 
                    $objServices = $em->getRepository('LostUserBundle:CountrywiseService')->findOneBy(array('country' => $countryId, 'services' => $service->getId()));
                    if($objServices) {
                        $errorArr[] = 'Service '.$service->getName().' already exist for Country '.$objService->getCountry()->getName();
                    }
             }
            
            if ($form->isValid() && empty($errorArr)) {

                foreach ($objService->getServices() as $service) {

                    $objCountrywiseService = new CountrywiseService();
                    $objCountrywiseService->setCountry($objService->getCountry());

                    $objCountrywiseService->setServices($service);
                    $objCountrywiseService->setStatus($objService->getStatus());
                    $em->persist($objCountrywiseService);
                }

                $em->flush();
                
                // set audit log add country wise services
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add country wise service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added country wise service successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Service(s) added successfully!");
                return $this->redirect($this->generateUrl('lost_countrywiseservice_list'));
            }
        }

        return $this->render('LostAdminBundle:CountrywiseService:new.html.twig', array('form' => $form->createView(), 'error' => $errorArr));
        
    }

    public function editAction(Request $request, $id) {
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('country_service_update')) {        
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update country wise service.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
    
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $service = $em->getRepository('LostUserBundle:CountrywiseService')->find($id);

        if (!$service) {
            $this->get('session')->getFlashBag()->add('failure', "Countrywise Service does not exist.");
            return $this->redirect($this->generateUrl('lost_countrywiseservice_list'));
        }
        
        $form = $this->createForm(new CountrywiseServiceFormType($service), $service);
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $em->persist($service);
                $em->flush();
                
                // set audit log update country wise services
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit country wise service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated country wise service";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Countrywise Service updated successfully!");
                return $this->redirect($this->generateUrl('lost_countrywiseservice_list'));
            }
        }

        return $this->render('LostAdminBundle:CountrywiseService:edit.html.twig', array('form' => $form->createView(), 'id' => $id, 'service' => $service));
        
    }

    public function deleteAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('country_service_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete country wise service.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        // set audit log delete country wise services
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete country wise service';

        
        $service = $em->getRepository('LostUserBundle:CountrywiseService')->find($id);

        if ($service) {
            $em->remove($service);
            $em->flush();
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted country wise service";
            $this->get('session')->getFlashBag()->add('success', "Countrywise Service deleted successfully!");
            
        } else {
           
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has tried to delete country wise service";
            $this->get('session')->getFlashBag()->add('failure', "Countrywise Service does not exist.");
        }
        
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        return $this->redirect($this->generateUrl('lost_countrywiseservice_list'));
    }
}