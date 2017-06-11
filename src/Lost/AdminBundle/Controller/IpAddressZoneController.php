<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\Service;
use Lost\AdminBundle\Form\Type\IpAddressZoneFormType;
use Lost\AdminBundle\Form\Type\ServiceLocationFormType;
use Lost\AdminBundle\Entity\IpAddressZone;
use Lost\AdminBundle\Entity\ServiceLocation;
use \DateTime;

class IpAddressZoneController extends Controller {

    public function indexAction(Request $request) {
        
        if(! $this->get('admin_permission')->checkPermission('service_location_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view service location list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();

        $query = $em->getRepository('LostAdminBundle:ServiceLocation')->getAllServiceLocation();
        
        $searchParams = $request->query->all();
        
        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            
            // set audit log search ip address range
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Search ip address range';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched " . json_encode($searchParams);
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $query = $em->getRepository('LostAdminBundle:ServiceLocation')->getAllServiceLocationSearch($query, $searchParams);
        }
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);
        
        return $this->render('LostAdminBundle:IpAddressZone:index.html.twig', array('pagination' => $pagination));
    }
    
    public function newAction(Request $request) {
        
        if(! $this->get('admin_permission')->checkPermission('service_location_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add service location.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $objServiceLocation = new ServiceLocation();
        $objIpAddressZone   = new IpAddressZone();
        
        $objServiceLocation->addIpAddressZone($objIpAddressZone);
        
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new ServiceLocationFormType(), $objServiceLocation);

        
            
            if ($request->getMethod() == "POST") {
                
                $form->handleRequest($request);
                
                if ($form->isValid()) {
                    
                    $objServiceLocation = $form->getData();
                    $em->persist($objServiceLocation);
                    
                    if($objServiceLocation->getIpAddressZones()){
                        
                        foreach ($objServiceLocation->getIpAddressZones() as $ipAddressZone){
                            
                            $ipAddressZone->setServiceLocation($objServiceLocation);
                            $em->persist($ipAddressZone);
                        }
                    }
                    $em->flush();
                    
                    // set audit log add ip address range
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Add IP address range';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added ip address range successfully";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $this->get('session')->getFlashBag()->add('success', "IP address range added successfully!");
                    return $this->redirect($this->generateUrl('lost_admin_ip_zone_list'));
                }
            }
            
            return $this->render('LostAdminBundle:IpAddressZone:new.html.twig', array('form' => $form->createView()));
        
    }
    
    public function editAction(Request $request, $id) {
        
        if(! $this->get('admin_permission')->checkPermission('service_location_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update service location.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objServiceLocation = $em->getRepository('LostAdminBundle:ServiceLocation')->find($id);

        if (!$objServiceLocation) {
            $this->get('session')->getFlashBag()->add('failure', "IP Address range does not exist.");
            return $this->redirect($this->generateUrl('lost_admin_ip_zone_list'));
        }


        $form = $this->createForm(new ServiceLocationFormType($objIpAddressZone), $objServiceLocation);
        
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objServiceLocation = $form->getData();
                $em->persist($objServiceLocation);

                if($objServiceLocation->getIpAddressZones()){

                    $ipZoneUpdatedId = array();
                    foreach ($objServiceLocation->getIpAddressZones() as $ipAddressZone){

                        $ipAddressZone->setServiceLocation($objServiceLocation);
                        $em->persist($ipAddressZone);

                        $ipZoneUpdatedId[] = $ipAddressZone->getId();
                    }
                    
                    $deleteIpZoneList = $em->getRepository('LostAdminBundle:IpAddressZone')->getRemoveIpZoneList($id,$ipZoneUpdatedId);
                    
                    if($deleteIpZoneList){
                    
                        foreach ($deleteIpZoneList as $deleteIpZone){
                    
                            $em->remove($deleteIpZone);
                        }                        
                    }
                }
                $em->flush();
                
                // set audit log edit ip address range
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit IP address range';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated IP address range";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "IP address range updated successfully!");
                return $this->redirect($this->generateUrl('lost_admin_ip_zone_list'));
            }
        }
        return $this->render('LostAdminBundle:IpAddressZone:edit.html.twig', array('form' => $form->createView(), 'id' => $id));        
    }
    
    public function deleteAction(Request $request, $id) {
        
        if(! $this->get('admin_permission')->checkPermission('service_location_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete service location.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        // set audit log delete ip address range
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete IP address range';
        
        $objServiceLocation = $em->getRepository('LostAdminBundle:ServiceLocation')->find($id);
        
        if ($objServiceLocation) {
            
            $em->remove($objServiceLocation);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "IP zone deleted successfully!");
            
            /* START: add user audit log for Delete IP address range-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted IP Zone ".$objServiceLocation->getName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            /* END: add user audit log for delete service-list */

        } else {
                
            /* START: add user audit log for Delete IP address range-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete IP zone ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            /* END: add user audit log for delete IP address range-list */
            
            $this->get('session')->getFlashBag()->add('failure', "IP address range does not exist.");
                
        }
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        return $this->redirect($this->generateUrl('lost_admin_ip_zone_list'));
    }
    
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }
}
