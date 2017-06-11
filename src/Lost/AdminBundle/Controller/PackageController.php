<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\ServiceBundle\Entity\Package;
use Lost\AdminBundle\Form\Type\PackageFormType;
use Lost\UserBundle\Entity\UserActivityLog;

class PackageController extends Controller {

    public function indexAction(Request $request) {
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('LostServiceBundle:Package')->getAllPackages();

        $searchParams = $request->query->all();

        if (isset($searchParams)) {
            
            // set audit log search package
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Search Package';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched " . json_encode($searchParams);
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $query = $em->getRepository('LostServiceBundle:Package')->getPackagesSearch($query, $searchParams);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:Package:index.html.twig', array(
                    'pagination' => $pagination
        ));
    }

    public function newAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPackage = new Package();
        $form = $this->createForm(new PackageFormType(), $objPackage);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {

                $objPackage = $form->getData();

                $em->persist($objPackage);
                $em->flush();
                
                // set audit log add package
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Package';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added package " . $objPackage->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Package added successfully.');
                return $this->redirect($this->generateUrl('lost_admin_package_list'));
            }
        }
        return $this->render('LostAdminBundle:SupportLocation:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    public function editAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();

        $objPackage = $em->getRepository('LostServiceBundle:Package')->find($id);
        
        if (!$objPackage) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find package.");
            return $this->redirect($this->generateUrl('lost_admin_package_list'));
        }
        
        $form = $this->createForm(new PackageFormType(), $objPackage);
        
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objPackage = $form->getData();

                $em->persist($objPackage);
                $em->flush();
                
                // set audit log add package
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Package';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated package " . $objPackage->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Package updated successfully.');
                return $this->redirect($this->generateUrl('lost_admin_package_list'));
            }
        }

        return $this->render('LostAdminBundle:Package:edit.html.twig', array(
                    'form' => $form->createView(),
                    'package' => $objPackage
        ));
    }
    
    public function deleteAction(Request $request) {
        
        $id = $request->get('id');
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPackage = $em->getRepository('LostServiceBundle:Package')->find($id);

        if ($objPackage) {
            
            // set audit log delete package
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Delete Package';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted package " . $objPackage->getName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->remove($objPackage);
            $em->flush();
            
            $this->get('session')->getFlashBag()->add('success', 'Package deleted successfully.');
        } else {
            $this->get('session')->getFlashBag()->add('failure', 'Unable to find package.');
        }
        
        return $this->redirect($this->generateUrl('lost_admin_package_list'));
    }
    
    public function deleteCompensationAction(Request $request) {
        
        $id = $request->get('id');
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objCompensation = $em->getRepository('LostUserBundle:Compensation')->find($id);
         $activityLog = array();
         $activityLog['admin'] = $admin;
         $activityLog['activity'] = 'Delete compensation';

        if ($objCompensation) {
            
            // set audit log delete compensation
           
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted compensation " . $objCompensation->getTitle();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->remove($objCompensation);
            $em->flush();
            $result = array('type' => 'success', 'message' => 'Compensation deleted successfully!');
            //$this->get('session')->getFlashBag()->add('success', 'Compensation deleted successfully.');
        
        } else {
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has tried to delete compensation " . $objCompensation->getTitle();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
         //   $this->get('session')->getFlashBag()->add('failure', 'Unable to find compensation.');
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete compensation!');
        }
        
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }
    
}
