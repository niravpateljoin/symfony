<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\Compensation;
use Lost\AdminBundle\Form\Type\CompensationFormType;
use Doctrine\ORM\EntityRepository;
use \DateTime;

class CompensationController extends Controller {

    public function indexAction(Request $request) {
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('compensation_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view compensation list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();        
        $query = $em->getRepository('LostUserBundle:Compensation')->getAllCompensation();  
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);
        
        return $this->render('LostAdminBundle:Compensation:index.html.twig', array(
                        'pagination' => $pagination
        ));
    }
    
    public function compensationListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $compensationColumns = array('Id','Title','ISPHours','IPTVDays','Services','Customers','ServiceLocations','CronStatus','Status','EmailSet?','Action');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($compensationColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'c.id';
            $sortOrder = 'ASC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'c.id';
            }
            
            if ($gridData['order_by'] == 'Title') {
                
                $orderBy = 'c.title';
            }
            if ($gridData['order_by'] == 'ISPHours') {
                
                $orderBy = 'c.ispHours';
            }
            if ($gridData['order_by'] == 'IPTVDays') {
                
                $orderBy = 'c.iptvDays';
            }
            if ($gridData['order_by'] == 'CronStatus') {
                
                $orderBy = 'c.status';
            }
            if ($gridData['order_by'] == 'Status') {
                
                $orderBy = 'c.isActive';
            }   
            if ($gridData['order_by'] == 'EmailSet?') {
                
                $orderBy = 'c.isEmailActive';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('LostUserBundle:Compensation')->getCompensationGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        
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
                                
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'<span>';
                            } else {
                                
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName()."</span>";
                            }
                            $count++;
                        }
                    }
                    
                    $customerCount = count($resultRow->getUsers());
                     
                    $customCount = 1;
                    $customerName = '';
                    if($resultRow->getUsers()){
                        foreach ($resultRow->getUsers() as $user) {
                            
                            if ($customCount == $customerCount) {
                                
                                $customerName .= $user->getName();
                            } else {
                                
                                $customerName .= $user->getName().", ";
                            }
                            $customCount++;
                        }
                    }
                    
                    $serviceLocationCount = count($resultRow->getServiceLocations());
                     
                    $locationCount = 1;
                    $locationName = '';
                    if($resultRow->getServiceLocations()){
                        foreach ($resultRow->getServiceLocations() as $location) {
                            
                            if ($locationCount == $serviceLocationCount) {
                                
                                $locationName .= $location->getName();
                            } else {
                                
                                $locationName .= $location->getName().", ";
                            }
                            $locationCount++;
                        }
                    }
                  
                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getTitle();
                    $row[] = $resultRow->getIspHours();
                    $row[] = $resultRow->getIptvDays(); 
                    $row[] = $serviceName;
                    $row[] = $customerName;
                    $row[] = $locationName;
                    $row[] = $resultRow->getStatus(); 
                    $row[] = $resultRow->getIsActive() == 'true' ? '<span class="btn btn-success btn-sm">'.'Active'.'<span>' : '<span class="btn btn-success btn-sm">'.'Inactive'.'</span>';
                    $row[] = $resultRow->getIsEmailActive() == 'true' ? '<span class="btn btn-success btn-sm">'.'Active'.'<span>' : '<span class="btn btn-success btn-sm">'.'Inactive'.'</span>';
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request            
     * @return type
     */
    public function newAction(Request $request) {

        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('compensation_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add compensation.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $activityLog = array(
                        'admin' => $admin,
        );
        
        $em = $this->getDoctrine()->getManager();
        $compensation = new Compensation();
        $form = $this->createForm(new CompensationFormType(), $compensation);
        
        if($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if($form->isValid()) {
                
                $compensation->setAdminId($admin->getId());
                $em->persist($compensation);
                $em->flush();
                
                $this->get('session')->getFlashBag()->add('success', "Compensation added successfully!");
                return $this->redirect($this->generateUrl('lost_admin_compensation_list'));
            }
        }
        
        return $this->render('LostAdminBundle:Compensation:new.html.twig', array(
                        'form' => $form->createView()
        ));
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request            
     * @return type
     */
    public function editAction(Request $request, $id) {

        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('compensation_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update compensation.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $activityLog = array(
                        'admin' => $admin,
        );
        
        $em = $this->getDoctrine()->getManager();
        
        $compensation = $em->getRepository('LostUserBundle:Compensation')->find($id);

        $form = $this->createForm(new CompensationFormType(), $compensation);
        
        if($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if($form->isValid()) {
                
                $serviceArr = array();
                
                if($compensation->getServices()){
                    
                    foreach ($compensation->getServices() as $service){
                        
                        $serviceArr[] = strtoupper($service->getName());                         
                    }
                    
                    if(!in_array('IPTV',$serviceArr)){
                    
                        $compensation->setIptvDays(NULL);
                    }
                    
                    if(!in_array('ISP',$serviceArr)){
                    
                        $compensation->setIspHours(NULL);
                    }
                }
                
                
                
                $compensation->setAdminId($admin->getId());
                $em->persist($compensation);
                $em->flush();
                
                $this->get('session')->getFlashBag()->add('success', "Compensation updated successfully!");
                return $this->redirect($this->generateUrl('lost_admin_compensation_list'));
            }
        }
        
        return $this->render('LostAdminBundle:Compensation:edit.html.twig', array(
                        'form'         => $form->createView(),
                        'compensation' => $compensation
        ));
    }

    
    
    public function validateDataAction(Request $request){
        
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('compensation_create') && $this->get('admin_permission')->checkPermission('compensation_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add compensation.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        $compensation = new Compensation();
        $form = $this->createForm(new CompensationFormType(), $compensation);
        
        $jsonResponse = array();
        $jsonResponse['error'] = '';
        $jsonResponse['status'] = '';
        
        if($request->getMethod() == "POST") {
        
            $form->handleRequest($request);
        
            $data = $form->getData();
            
            if($data){
               
                if(!$data->getTitle()){

                    $jsonResponse['error']['title'] = 'Please enter title.';
                }
                
                if(count($data->getServices()) <= 0){
                    
                    $jsonResponse['error']['services'] = 'Please select service.';
                }else{
                    
                    foreach ($data->getServices() as $service){
                        
                        if(strtoupper($service->getName()) == 'IPTV'){
                            
                            if(!$data->getIptvDays()){
                            
                                $jsonResponse['error']['iptvDays'] = 'Please enter IPTV Compensation Days.';
                            }else if(!preg_match('/^\d+$/',$data->getIptvDays())){
                            
                                $jsonResponse['error']['iptvDays'] = 'Please enter valid IPTV Compensation Days.';
                            }
                        }
                        
                        if(strtoupper($service->getName()) == 'ISP'){
                            
                            if(!$data->getIspHours()){
                            
                                $jsonResponse['error']['ispHours'] = 'Please enter ISP Compensation Hours.';
                            }else if(!preg_match('/^\d+$/',$data->getIspHours())){
                            
                                $jsonResponse['error']['ispHours'] = 'Please enter valid ISP Compensation Hours.';
                            }                            
                        }
                    }
                }
                
                if(!$data->getType()){
                
                    $jsonResponse['error']['type'] = 'Please select compensation type.';
                }else{
                    
                    if($data->getType() == 'ServiceLocation'){

                        if(count($data->getServiceLocations()) <= 0){
                            
                            $jsonResponse['error']['serviceLocations'] = 'Please select service location.';
                        }
                    }
                    if($data->getType() == 'User'){
                        
                        if(count($data->getUsers()) <= 0){
                            
                            $jsonResponse['error']['users'] = 'Please select customer.';
                        }
                    }
                }
                if(!$data->getStatus()){

                    $jsonResponse['error']['status'] = 'Please select compensation status.';
                }
                
                if($data->getIsActive() == ""){
                    
                    if($data->getIsActive() != '0') {
                        
                        $jsonResponse['error']['isActive'] = 'Please select status.';
                    }
                }
                
                if($data->getIsEmailActive()){
                
                    if(!$data->getEmailSubject()){

                        $jsonResponse['error']['emailSubject'] = 'Please enter subject.';
                    }
                    if(!$data->getEmailContent()){
                    
                        $jsonResponse['error']['emailContent'] = 'Please enter email body.';
                    }                    
                }  

                if($jsonResponse['error'] == ''){
                    
                    $jsonResponse['status'] = 'success';
                }
            }else{
                
                $jsonResponse['status'] = 'failed';
            }
        }else{
            
            $jsonResponse['status'] = 'failed';
        }
        echo json_encode($jsonResponse);
        exit;
    }
    
    public function searchUserAction(Request $request) {
    
        $em = $this->getDoctrine()->getManager();
        $tag = $request->get('tag');
        $service = $request->get('service');
    
        $jsonData = array();
    
        if($tag && $service){
            
            $expService = explode(',',$service);
    
            $users = $em->getRepository('LostUserBundle:User')->getSearchUser($tag,$expService);
    
            if($users){
    
                foreach ($users as $user){
    
                    $tempArr = array();
    
                    $name   = $user->getFirstName().' '.$user->getLastName();
                    $userid = $user->getId();
    
                    $tempArr['key']   = (string) $userid;
                    $tempArr['value'] = $name;
    
                    $jsonData[] = $tempArr;
                }
            }
            echo json_encode($jsonData);
            exit;
        }
        
        echo '';exit;
    }
    
    public function searchServiceLocationAction(Request $request) {
    
        $em = $this->getDoctrine()->getManager();
        $tag     = $request->get('tag');
        $service = $request->get('service');
        
        $jsonData   = array();
        $expService = array();
        
        if($tag && $service){
            
            $expService = explode(',',$service);
    
            $serviceLocations = $em->getRepository('LostAdminBundle:ServiceLocation')->getSearchServiceLocation($tag,$expService);
    
            if($serviceLocations){
    
                foreach ($serviceLocations as $serviceLocation){
    
                    $tempArr = array();
                    
                    $country = '';
                    if($serviceLocation->getCountry()){
                        
                        $country = $serviceLocation->getCountry()->getName();
                    }
                    $locationName     = $serviceLocation->getName();
                    $locationId       = $serviceLocation->getId();
    
                    $tempArr['key']   = (string) $locationId;
                    $tempArr['value'] = $locationName;
    
                    $jsonData[] = $tempArr;
                }
            }
            echo json_encode($jsonData);
            exit;
        }
        echo '';exit;
    }
    
    public function removeAutoCompleteDataAction(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        $selectedService         = $request->get('selectedService');
        $selectedServiceLocation = $request->get('selectedServiceLocation');
        $selectedUser            = $request->get('selectedUser');
        $compensation_type       = $request->get('compensation_type');
        
        $serviceIds        = array();
        $availableLocation = array();
        $availableUser     = array();
        $jsonResponse      = array();
        $jsonResponse['status']     = '';
        $jsonResponse['removedIds'] = '';
        if($selectedService && $compensation_type){
            
            $serviceIds = explode(',',$selectedService); 
            
            if($compensation_type == 'ServiceLocation' && $selectedServiceLocation){
                
                $jsonResponse['removedIds'] = $selectedServiceLocation;
                $serviceLocations = $em->getRepository('LostAdminBundle:ServiceLocation')->getAvailableLocationByService($serviceIds,$selectedServiceLocation);
                
                if($serviceLocations){

                    foreach ($serviceLocations as $serviceLocation){
                        
                        $availableLocation[] = $serviceLocation->getId();
                    }
                }
                
                if(count($availableLocation) > 0){
                    
                    $jsonResponse['removedIds'] = array_diff($selectedServiceLocation, $availableLocation);
                }                
            }
            
            if($compensation_type == 'Customer' && $selectedUser){
                
                $jsonResponse['removedIds'] = $selectedUser;
                $users = $em->getRepository('LostUserBundle:User')->getUserByActiveService($serviceIds,$selectedUser);
                
                if($users){
                
                    foreach ($users as $user){
                
                        $availableUser[] = $user->getId();
                    }
                }
                
                if(count($availableUser) > 0){
                
                    $jsonResponse['removedIds'] = array_diff($selectedUser, $availableUser);
                }
            }
        }
        
        if(count($jsonResponse['removedIds']) > 0){
            
            $jsonResponse['status']     = 'success';            
        }
        
        echo json_encode($jsonResponse);
        exit;
    }
    
    public function previewCompensationAction(Request $request){
        
        $view = array();
        $em = $this->getDoctrine()->getManager();
        
        $compensation = new Compensation();
        $form = $this->createForm(new CompensationFormType(), $compensation);
        
        $data = array();
        if($request->getMethod() == "POST") {
        
            $form->handleRequest($request);
        
            $data = $form->getData();
        }
        
        $view['data'] = $data;
            
        return $this->render('LostAdminBundle:Compensation:previewCompensation.html.twig', $view);
    }
}
