<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Lost\UserBundle\Entity\EmailCampaign;
use Lost\AdminBundle\Form\Type\EmailCampaignSearchFormType;
use Lost\AdminBundle\Form\Type\EmailCampaignFormType;
use Lost\UserBundle\Entity\UserActivityLog;

class EmailCampaignController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('email_campaign_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view email campaign list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        
        $query = $em->getRepository('LostUserBundle:EmailCampaign')->getAllEmails();
        $form = $this->createForm(new EmailCampaignSearchFormType(), array('searchParams' => isset($searchParams['search']) ? $searchParams['search'] : ''));
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
                $query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:EmailCampaign:index.html.twig', array(
                    'pagination' => $pagination,
                    'form' => $form->createView(),
        ));
    }
    
   public function emailCampaignListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $emailCompaignColumns = array('Id','Subject','EmailType','Service','Status');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($emailCompaignColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'e.id';
            $sortOrder = 'ASC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'e.id';
            }
            
            if ($gridData['order_by'] == 'Subject') {
                
                $orderBy = 'e.subject';
            }
            if ($gridData['order_by'] == 'EmailType') {
                
                $orderBy = 'e.emailType';
            }
            if ($gridData['order_by'] == 'Service') {
                
                $orderBy = 'e.services';
            }
          
            if ($gridData['order_by'] == 'Status') {
                
                $orderBy = 'e.emailStatus';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('LostUserBundle:EmailCampaign')->getEmailCampaignGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        
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
                   
                    $count = 1;
                    $servicesCount = count($resultRow->getServices());

                    $serviceName = '';
                    if($resultRow->getServices()){
                        foreach ($resultRow->getServices() as $service) {
                            
                            if ($count == $servicesCount) {
                                
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
                            } else {
                                
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
                            }
                            $count++;
                        }
                    }
                  
                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getSubject();
                    $row[] = $resultRow->getEmailType() == 'M' ? 'Marketing' : 'Support';
                    $row[] = $serviceName;                  
                    $row[] = '<span class="btn btn-success btn-sm service">'.$resultRow->getEmailStatus().'</span>';
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
        if(! $this->get('admin_permission')->checkPermission('email_campaign_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add email campaign.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
            
        $objEmailCampaign = new EmailCampaign();
        $form = $this->createForm(new EmailCampaignFormType(), $objEmailCampaign);
       
        if ($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            if ($form->isValid()) {
                
                $formData = $form->getData();
                $objEmailCampaign->setSubject($formData->getSubject());
                $objEmailCampaign->setMessage($formData->getMessage());
                /* $objEmailCampaign->setStartDate($formData->getStartDate());
                $objEmailCampaign->setEndDate($formData->getEndDate()); */
                $objEmailCampaign->setEmailType($formData->getEmailType());

                $em->persist($objEmailCampaign);
                $em->flush();
                
                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Email Campaign';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has added email campaign ".$formData->getSubject();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'An email Campaign added successfully.');
                return $this->redirect($this->generateUrl('lost_admin_email_campaign_list'));
            }
        }
        return $this->render('LostAdminBundle:EmailCampaign:new.html.twig', array(
                    'form' => $form->createView(),
        ));
        
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('email_campaign_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update email campaign.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
            
        $objEmailCampaign = $em->getRepository('LostUserBundle:EmailCampaign')->find($id);
        
        if (!$objEmailCampaign) {
            
            $this->get('session')->getFlashBag()->add('failure', "Unable to find email campaign.");
            return $this->redirect($this->generateUrl('lost_admin_email_campaign_list'));
        }
        
        $form = $this->createForm(new EmailCampaignFormType(), $objEmailCampaign);

        if ($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if ($form->isValid()) {
                
                $formData = $form->getData();

                $objEmailCampaign->setSubject($formData->getSubject());
                $objEmailCampaign->setMessage($formData->getMessage());
                /* $objEmailCampaign->setStartDate($formData->getStartDate());
                $objEmailCampaign->setEndDate($formData->getEndDate()); */
                $objEmailCampaign->setEmailType($formData->getEmailType());

                $em->persist($objEmailCampaign);
                $em->flush();
                
                // set audit log update email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Email Campaign';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated email campaign ".$formData->getSubject();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'An email Campaign updated successfully.');
                return $this->redirect($this->generateUrl('lost_admin_email_campaign_list'));
            }
        }

        return $this->render('LostAdminBundle:EmailCampaign:edit.html.twig', array(
                    'form' => $form->createView(),
                    'email' => $objEmailCampaign
        ));        
    }

    public function deleteAction(Request $request) {
          $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('email_campaign_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete email campaign.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
            
        $objEmailCampaign = $em->getRepository('LostUserBundle:EmailCampaign')->find($id);
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Email Campaign';
        if ($objEmailCampaign) {
            
            // set audit log delete email campagin
           
            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted email campaign ".$objEmailCampaign->getSubject();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->remove($objEmailCampaign);
            $em->flush();
             $result = array('type' => 'success', 'message' => 'Email compaign deleted successfully!');
        
        } else {
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete email campaign ".$objEmailCampaign->getSubject();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete email campaign!');
        }        
         $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }

    public function sendEmailAction(Request $request, $id) {
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('email_campaign_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to send email campaign.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();

        $objEmailCampaign = $em->getRepository('LostUserBundle:EmailCampaign')->find($id);
        
        if ($objEmailCampaign) {
            
            // set audit log send email campagin
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Send Email Campaign';
            $activityLog['description'] = "Admin ".$admin->getUsername()." has marked email campaign to send " . $objEmailCampaign->getSubject();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $objEmailCampaign->setEmailStatus('Active');
            $em->persist($objEmailCampaign);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Email campaign marked active to send");
            return $this->redirect($this->generateUrl('lost_admin_email_campaign_list'));
        } else {
            
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to send an email!");
            return $this->redirect($this->generateUrl('lost_admin_email_campaign_list'));
        }        
    }
}
