<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\UserMacAddress;
use Symfony\Component\HttpFoundation\JsonResponse;
use Lost\UserBundle\Form\Type\UserMacAddressFormType;

/**
 * 
 */
class UserMacAddressController extends Controller {

    // add and edit user mac address
    public function macAddressAction(Request $request, $id, $type, $userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('LostUserBundle:User')->find($userId);
        
        if($type != 'add' && $id != 0) {
            
            $objUserMacAddress = $em->getRepository('LostUserBundle:UserMacAddress')->find($id);
            
        } else {
            
            $objUserMacAddress = new UserMacAddress();
        }
        
        $userMacAddressForm = $this->createForm(new UserMacAddressFormType(), $objUserMacAddress);

        $response = array('status' => 'failure');
        
        $objMacAddress =  $em->getRepository('LostUserBundle:UserMacAddress')->findBy(array('user' => $user, 'isDelete' => 0));
        
        if ($request->isXmlHttpRequest()) {
            
            if($type == 'add') {
        
                if($objMacAddress && count($objMacAddress) > $request->getSession()->get('maxMacAddress') || count($objMacAddress) == $request->getSession()->get('maxMacAddress')) {
                    
                    $response['failure'] = 'You can add maximum '.$request->getSession()->get('maxMacAddress').' mac addresses.';
                    echo json_encode($response);
                    exit;

                }
            }
            
            if ($request->getMethod() == "POST") {

                $userMacAddressForm->handleRequest($request);
                
                if ($userMacAddressForm->isValid()) {
                    
                        $formData =  $userMacAddressForm->getData();
                        
                        $macAddress = substr(str_replace(":", "", $formData->getMacAddress()), -6);
                        $serialNumber = "Lost-".$macAddress;
                        
                        // add mac address in selevision
                        
                        // #########START##########
                        
                        $wsParam['mac'] = $formData->getMacAddress();
                        $wsParam['serial'] = $serialNumber;
                        
                        $selevisionService = $this->get('selevisionService');
                        $wsResponse = $selevisionService->callWSAction('registerMac', $wsParam);
                                                
                        #########END##########
                        
                        ### Set Mac Address ###
                        $selevisionService = $this->get('selevisionService');

                        $wsSetMacAddressParam['mac'] = $formData->getMacAddress();
                        $wsSetMacAddressParam['serial'] = $serialNumber;
                        $wsSetMacAddressParam['action'] = 'set';
                        $wsSetMacAddressParam['cuLogin'] = $user->getUsername();
                        $wsResponseSetMacAddress = $selevisionService->callWSAction('customerMac', $wsSetMacAddressParam);
                        
                        
                    // Set audit log for redund amount
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    
                    if($type == 'add') {
                        
                        $activityLog['activity'] = 'Add user mac address';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " has add mac address " . $formData->getMacAddress();
                        
                    }
                    else {
                        
                        $activityLog['activity'] = 'Edit user mac address';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " has update mac address " . $formData->getMacAddress();
                        
                    }
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $objUserMacAddress->setUser($user);
                    $em->persist($objUserMacAddress);
                    $em->flush();
                    
                    $response['status'] = 'success';
                    
                    
                } else {
                    
                    if($type == 'add') {
                        
                     $macAddressform = $this->render('LostAdminBundle:UserMacAddress:new.html.twig', array('form' => $userMacAddressForm->createView()));
                     $response['error'] =  $macAddressform->getContent();
                     
                    } else {
                        
                     $userFormdata = $this->render('LostAdminBundle:UserMacAddress:edit.html.twig', array('form' => $userMacAddressForm->createView(), 'macAddress' => $objUserMacAddress));
                     $response['error'] =  $userFormdata->getContent();
                        
                    }
                    
                }
            }
        }
        
        echo json_encode($response);
        exit;
    }
    // list of user mac address
    public function listMacAddressAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('LostUserBundle:User')->find($id);
        
        $response = array('status' => 'failure', 'totalMacAddress' => 0);
       
         if($user) {
             
            $objMacAddress =  $em->getRepository('LostUserBundle:UserMacAddress')->findBy(array('user' => $user, 'isDelete' => 0));
            
            $macAddressList = array();
            
            if($objMacAddress) {
                
                $response['totalMacAddress'] = count($objMacAddress);
                
                foreach($objMacAddress as $key => $macAddress) {
                    
                    $macAddressList[$key]['id'] = $macAddress->getId();
                    $macAddressList[$key]['macAddress'] = $macAddress->getMacAddress();
                    
                }
                
                $data = $this->render('LostAdminBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                
                $response['status'] = $data->getContent();
                echo json_encode($response);
                exit;
                
            }  
               
         } 
         
         echo json_encode($response);
         exit;
        
    }
    
    // delete mac address
    public function deleteMacAddressAction(Request $request, $id, $userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('LostUserBundle:User')->find($userId);
        
        $response = array('status' => 'failure', 'totalMacAddress' => 0);
        
         if($user) {
             
            // remove record 
            if($id && $id != 0) {
                 
                  $macAddress = $em->getRepository('LostUserBundle:UserMacAddress')->find($id);
                    
                    if($macAddress) {
                        
                            $serialNumber = "Lost-".substr(str_replace(":", "", $macAddress->getMacAddress()), -6);
                            
                            ### Unset Mac Address ###
                            $selevisionService = $this->get('selevisionService');

                            $wsUnsetMacAddressParam['mac'] = $macAddress->getMacAddress();
                            $wsUnsetMacAddressParam['serial'] = $serialNumber;
                            $wsUnsetMacAddressParam['action'] = 'get';
                            $wsUnsetMacAddressParam['cuLogin'] = $user->getUsername();
                            $wsResponseUnsetMacAddress = $selevisionService->callWSAction('customerMac', $wsUnsetMacAddressParam);
                            
                            
                            $macAddress->setIsDelete(1);
                            $em->persist($macAddress);
                            $em->flush();
                            
                            
                            // Set audit log for delete user mac address
                            $activityLog = array();
                            $activityLog['admin'] = $admin;
                            $activityLog['user'] = $user;
                            $activityLog['activity'] = 'Delete user mac address';
                            $activityLog['description'] = "Admin " . $admin->getUsername() . " has delete mac address ".$id;
                            $this->get('ActivityLog')->saveActivityLog($activityLog);

                        
                    }
                
                 $objMacAddress =  $em->getRepository('LostUserBundle:UserMacAddress')->findBy(array('user' => $user, 'isDelete' => 0));
                 
                 $macAddressList = array();
                 
                 if($objMacAddress) {
                     
                     $response['totalMacAddress'] = count($objMacAddress);
                
                        foreach($objMacAddress as $key => $macAddress) {

                            $macAddressList[$key]['id'] = $macAddress->getId();
                            $macAddressList[$key]['macAddress'] = $macAddress->getMacAddress();

                        }
                
                    $data = $this->render('LostAdminBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                    
                    $response['status'] = $data->getContent();
                    echo json_encode($response);
                    exit;
                    
                  
                }
                
                else {
                    
                    $data = $this->render('LostAdminBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                    
                    $response['status'] = $data->getContent();
                    echo json_encode($response);
                    exit;
                    
                }
             
            }  
               
         } 
         
         echo json_encode($response);
         exit;
        
    }
    
    // edit mac address
    public function addMacAddressAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        
        $form = $this->createForm(new UserMacAddressFormType());
                     
        $macAddressform = $this->render('LostAdminBundle:UserMacAddress:new.html.twig', array('form' => $form->createView()));
        
        echo $macAddressform->getContent();
        exit;
        
    }
    
    // edit mac address
    public function editMacAddressAction(Request $request, $id, $userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('LostUserBundle:User')->find($userId);
        
        $response = array();
        
         if($user) {
                
                 $objMacAddress = $em->getRepository('LostUserBundle:UserMacAddress')->find($id);
                    
                 if($objMacAddress) {
                     
                     $userMacAddressForm = $this->createForm(new UserMacAddressFormType(), $objMacAddress);
                     
                     $userFormdata = $this->render('LostAdminBundle:UserMacAddress:edit.html.twig', array('form' => $userMacAddressForm->createView(), 'macAddress' => $objMacAddress));
                     
                     echo $userFormdata->getContent();
                     exit;
                     
                 }
                 else {
                     
                     echo false;
                     exit;
                     
                 }
         }
         
        echo false;
        exit;
        
    }
    
    
}
