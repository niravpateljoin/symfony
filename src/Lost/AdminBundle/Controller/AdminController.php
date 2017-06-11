<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\AdminBundle\Form\Type\AdminFormType;
use Lost\UserBundle\Entity\User;
use Lost\AdminBundle\Form\Type\ChangePasswordFormType;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\CountrywiseService;
use Lost\AdminBundle\Form\Type\CountrywiseServiceFormType;
use \DateTime;

class AdminController extends Controller {

    public function indexAction(Request $request) {
        
        if(! $this->get('admin_permission')->checkPermission('admin_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view admin list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $query = $em->getRepository('LostUserBundle:User')->getAllAdminQuery($admin);
        
        $searchParams = $request->query->all();
        if (!empty($searchParams)) {

            if (isset($searchParams['search'])) {
                
                // Set activity log for search admin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Search admin';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " searched " . $searchParams['search'];
                
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
            }

            $em->getRepository('LostUserBundle:User')->getAdminSearch($query, $searchParams);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
                $query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:Admin:index.html.twig', array('pagination' => $pagination, 'admin' => $admin));
    }

    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function newAction(Request $request) {
        
        if(! $this->get('admin_permission')->checkPermission('admin_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new admin.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new AdminFormType($admin, null), new User());

        if ($request->getMethod() == "POST") {


            $form->handleRequest($request);
//             $formValues = $request->request->get('lost_admin_registration');

            if ($form->isValid()) {

                $objAdmin = $form->getData();
                $objAdmin->setRoles(array('ROLE_ADMIN'));
                $em->persist($objAdmin);
                $em->flush();
                
                // Set activity log add user
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $objAdmin;
                $activityLog['activity'] = 'Add admin';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new admin as Role: " . $objAdmin->getSingleRole() . ", Email: " . $objAdmin->getEmail() . " and Username: " . $objAdmin->getUsername();
                
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                
                $this->get('session')->getFlashBag()->add('success', "Admin added successfully!");
                return $this->redirect($this->generateUrl('lost_admin_list'));
            }
        }

        return $this->render('LostAdminBundle:Admin:new.html.twig', array('form' => $form->createView()));
        
    }

    public function editAction(Request $request, $id) {
        
        if(! $this->get('admin_permission')->checkPermission('admin_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update admin.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();        
        $user = $em->getRepository('LostUserBundle:User')->find($id);
        
        if($admin->getGroup() != 'Super Admin' && $admin->getId() != $user->getId()) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update this admin.");
            return $this->redirect($this->generateUrl('lost_admin_list'));
        }
        
        if (!$user) {
            $this->get('session')->getFlashBag()->add('failure', "Admin user does not exist.");
            return $this->redirect($this->generateUrl('lost_admin_list'));
        }
 
        $form = $this->createForm(new AdminFormType($admin, $user), $user);
        $changePasswordForm = $this->createForm(new ChangePasswordFormType(), $user);

        if ($request->getMethod() == "POST") {

            if ($request->request->has($form->getName())) {

                $username = $user->getUsername();
                $form->handleRequest($request);

                if ($form->isValid()) {

                    $objAdmin = $form->getData();
                    $objAdmin->setUsername($username);
                    $em->persist($objAdmin);
                    $em->flush();
                    
                    // Set activity log update admin user
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $objAdmin;
                    $activityLog['activity'] = 'Edit admin';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated admin " . $user->getUsername();

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $this->get('session')->getFlashBag()->add('success', "Admin updated successfully!");
                    return $this->redirect($this->generateUrl('lost_admin_list'));
                }
            }

            if ($request->request->has($changePasswordForm->getName())) {

                $changePasswordForm->handleRequest($request);

                if ($changePasswordForm->isValid()) {

                    $userManager = $this->get('fos_user.user_manager');
                    $userManager->updateUser($user);
                    
                    // Set activity log change password
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'Admin change password';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has changed password for admin " . $user->getUsername();

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");
                    return $this->redirect($this->generateUrl('lost_admin_list'));
                }
            }
        }

        return $this->render('LostAdminBundle:Admin:edit.html.twig', array(
                    'form' => $form->createView(),
                    'user' => $user,
                    'changePasswordForm' => $changePasswordForm->createView(),
        ));
        
    }

    public function deleteAction(Request $request, $id) {
        
        if(! $this->get('admin_permission')->checkPermission('admin_delete')) {
            
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delele admin.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $user = $em->getRepository('LostUserBundle:User')->find($id);
        
        if($user->getGroup() == $admin->getGroup() or $user->getGroup() == 'Super Admin') {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delele ".$admin->getGroup());
            return $this->redirect($this->generateUrl('lost_admin_list'));
        }
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['ip'] = $request->getClientIp();
        $activityLog['sessionId'] = $request->getSession()->getId();
        $activityLog['url'] = $request->getUri();

        if ($user) {
            
            // Set activity log change password
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['user'] = $user;
            $activityLog['activity'] = 'Delete admin';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted admin " . $user->getUsername();

            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $user->setIsDeleted(1);
            $user->setExpired(1);
            $user->setExpiresAt(new DateTime());
            $em->persist($user);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Admin deleted successfully!");
            
        } else {
            $this->get('session')->getFlashBag()->add('failure', "User does not exist.");
        }

        return $this->redirect($this->generateUrl('lost_admin_list'));
    }

    public function changePasswordAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $changePasswordForm = $this->createForm(new \Lost\UserBundle\Form\Type\ChangePasswordFormType, $admin);

        if ($request->getMethod() == "POST") {

            $changePasswordForm->handleRequest($request);

            if ($changePasswordForm->isValid()) {
                
                // set audit log admin user change password
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Change password';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " change password ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $userManager = $this->get('fos_user.user_manager');
                $userManager->updateUser($admin);
                $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");
            }
        }

        return $this->render('LostAdminBundle:Admin:changePassword.html.twig', array(
                    'changePasswordForm' => $changePasswordForm->createView()
        ));
    }
}
