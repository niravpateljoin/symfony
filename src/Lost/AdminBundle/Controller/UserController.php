<?php

namespace Lost\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Lost\UserBundle\Entity\User;
use Lost\AdminBundle\Form\Type\UserFormType;
use Lost\AdminBundle\Form\Type\UserSettingFormType;
use Lost\AdminBundle\Form\Type\ChangePasswordFormType;
use Lost\AdminBundle\Form\Type\LoginLogSearchFormType;
use \DateTime;
use Lost\UserBundle\Entity\UserActivityLog;
use Lost\UserBundle\Entity\UserService;
use Lost\UserBundle\Entity\UserServiceSetting;
use Lost\UserBundle\Entity\UserServiceSettingLog;
use Lost\UserBundle\Entity\UserSetting;
use Lost\UserBundle\Entity\ServiceLocation;
use Lost\ServiceBundle\Entity\PurchaseOrder;
use Lost\ServiceBundle\Entity\ServicePurchase;
use Lost\UserBundle\Entity\UserCreditLog;
use Lost\UserBundle\Entity\UserCredit;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user list.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();

        $admin = $this->get('security.context')->getToken()->getUser();

        $ipAddressZones = $em->getRepository('LostAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

        $query = $em->getRepository('LostUserBundle:User')->getAllUserQuery($ipAddressZones);

        $searchParams = $request->query->all();

        if (!empty($searchParams) && isset($searchParams['search']) && $searchParams['search']) {

            // set audit log search user
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Search user';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched " . $searchParams['search'];
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->getRepository('LostUserBundle:User')->getUserSearch($query, $searchParams);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        $objCredits = $em->getRepository('LostAdminBundle:Credit')->findAll();

        return $this->render('LostAdminBundle:User:index.html.twig', array(
                    'pagination' => $pagination,
                    'admin' => $admin,
                    'credits' => $objCredits
        ));
    }

    /* START - Customer Listing */

    public function newAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new user.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new UserFormType($admin, null), new User());

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            $formValues = $request->request->get('lost_admin_registration');

            if ($form->isValid()) {

                $objUser = $form->getData();
                $tokenGenerator = $this->get('fos_user.util.token_generator');
                $objUser->setConfirmationToken($tokenGenerator->generateToken());
                $em->persist($objUser);
                $em->flush();

                // set audit log add user
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $objUser;
                $activityLog['activity'] = 'Add user';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new user Email: " . $objUser->getEmail() . " and Username: " . $objUser->getUsername();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $body = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:register_email.html.twig', array(
                    'user' => $objUser,
                    'token' => $objUser->getConfirmationToken()
                ));

                $resend_email_verification = \Swift_Message::newInstance()->
                                // ->setSubject("Welcome " . $objUser->getUsername() . " to Lost!")
                                setSubject("Welcome " . $objUser->getName() . " to Lost Portal!")->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))->setTo($objUser->getEmail())->setBody($body->getContent())->setContentType('text/html');

                $this->container->get('mailer')->send($resend_email_verification);

                $this->get('session')->getFlashBag()->add('success', "Customer added successfully!");
                return $this->redirect($this->generateUrl('lost_admin_user_list'));
            }
        }

        return $this->render('LostAdminBundle:User:new.html.twig', array(
                    'form' => $form->createView()
        ));
    }

    /* END */

    /* START - Customer Delete Action */

    public function deleteAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete user.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('LostUserBundle:User')->find($id);

        if ($user) {

            $user->setIsDeleted(1);
            $user->setExpired(1);
            $user->setExpiresAt(new DateTime());
            $em->persist($user);
            $em->flush();

            // set audit log delete user
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['user'] = $user;
            $activityLog['activity'] = 'Delete user';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted user " . $user->getUsername();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $this->get('session')->getFlashBag()->add('success', "Customer deleted successfully!");
        } else {

            $this->get('session')->getFlashBag()->add('failure', "Customer does not exist.");
        }
        return $this->redirect($this->generateUrl('lost_admin_user_list'));
    }

    /* END */

    /* START - Customer Edit Action */

    public function editAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update user.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('LostUserBundle:User')->find($id);

        if (!$user) {
            $this->get('session')->getFlashBag()->add('failure', "Customer does not exist.");
            return $this->redirect($this->generateUrl('lost_admin_user_list'));
        }

        // SET parameters for user audit log for edit user
        $activityLog = array(
            'admin' => $admin,
            'ip' => $request->getClientIp(),
            'sessionId' => $request->getSession()->getId(),
            'url' => $request->getUri()
        );

        $email = $user->getEmail();
        $roles = $user->getRoles();

        $form = $this->createForm(new UserFormType(), $user);
        $changePasswordForm = $this->createForm(new ChangePasswordFormType(), $user);

        $userSetting = $em->getRepository('LostUserBundle:UserSetting')->getUserMaxSetting($id);

        if ($userSetting == null) {
            $userSetting = new UserSetting();
            $userSetting->setUser($user);
            $userSetting->setAdminId($admin->getId());
        }
        $userSettingForm = $this->createForm(new UserSettingFormType(), $userSetting);

        if ($request->getMethod() == "POST") {

            if ($request->request->has($form->getName())) {

                $username = $user->getUsername();
                $form->handleRequest($request);
                $formValues = $request->request->get('lost_admin_registration');

                if ($form->isValid()) {

                    if ($form->getData()->getEmail() != $email) {
                        $userManager = $this->get('fos_user.user_manager');
                        $tokenGenerator = $this->get('fos_user.util.token_generator');
                        $token = $tokenGenerator->generateToken();
                        $user->setConfirmationToken($token);
                        $user->setIsEmailVerified(0);
                        $user->setEmailVerificationDate(new DateTime());

                        $userManager->updateUser($user);

                        $body = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:resend_email_verification.html.twig', array(
                            'user' => $user,
                            'token' => $token
                        ));

                        $resend_email_verification = \Swift_Message::newInstance()->setSubject("Welcome " . $user->getUsername() . " to Lost!")->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))->setTo($user->getEmail())->setBody($body->getContent())->setContentType('text/html');

                        $this->container->get('mailer')->send($resend_email_verification);
                    }
                    $objUser = $form->getData();
                    $em->persist($objUser);
                    $em->flush();

                    // #########START##########
                    // check selevision api to check whether customer exist in system
                    $wsParam['cuLogin'] = $objUser->getUsername();

                    $selevisionService = $this->get('selevisionService');
                    $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

                    // if customer exists, update the details
                    if ($wsResponse['status'] == 1) {

                        // get account details for selevisions account
                        $accountArr = $request->request->get('lost_admin_registration');

                        // call selevisions service to update account
                        $wsParam['cuNewFirstName'] = $accountArr['firstname'];
                        $wsParam['cuNewLastName'] = $accountArr['lastname'];
                        $wsParam['cuNewEmail'] = $user->getEmail();

                        $wsResponse = $selevisionService->callWSAction('updateCustomer', $wsParam);
                    }
                    // #########END##########
                    // set audit log edit user
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'Edit user';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated user " . $user->getUsername();
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Customer updated successfully!");
                    return $this->redirect($this->generateUrl('lost_admin_user_list'));
                }
            }

            if ($request->request->has($changePasswordForm->getName())) {

                $changePasswordForm->handleRequest($request);

                if ($changePasswordForm->isValid()) {

                    // #########START##########
                    // check selevision api to check whether customer exist in system
                    $wsParam = array();
                    $wsParam['cuLogin'] = $user->getUsername();

                    $selevisionService = $this->get('selevisionService');
                    $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

                    if ($wsResponse['status'] == 1) {
                        // get plain password for selevisions
                        $changePasswordArr = $request->request->get('lost_admin_changepassword');

                        $currentPassword = $wsResponse['password'];
                        $newPassword = $changePasswordArr['plainPassword']['first'];

                        $seleVisionCurrrentPwd = $wsResponse['password'];
                    }
                    // #########END##########

                    $userManager = $this->get('fos_user.user_manager');
                    $userManager->updateUser($user);

                    // #########START##########
                    if ($wsResponse['status'] == 1 && ($user->getNewSelevisionUser() || $currentPassword != $newPassword)) {

                        // call selevisions service to update password
                        $wsParam['cuPwd'] = ($user->getNewSelevisionUser()) ? $seleVisionCurrrentPwd : $currentPassword;
                        $wsParam['cuNewPwd1'] = $newPassword;
                        $wsParam['cuNewPwd2'] = $newPassword;

                        $wsResPwd = $selevisionService->callWSAction('changeCustomerPwd', $wsParam);

                        if ($wsResPwd['status'] == 1) {

                            $user->setNewSelevisionUser(0);
                            $em->persist($user);
                            $em->flush();
                        }
                    }
                    // #########END##########
                    // set audit log edit user
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'Change user password';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has changed password for user " . $user->getUsername();
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");
                    return $this->redirect($this->generateUrl('lost_admin_user_list'));
                }
            }


            if ($request->request->has($userSettingForm->getName())) {
                $userSettingForm->handleRequest($request);
                if ($userSettingForm->isValid()) {

                    $objSetting = $userSettingForm->getData();

                    $em->persist($objSetting);
                    $em->flush();

                    // set audit log edit user
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'User settings';
                    $activityLog['description'] = "Admin '" . $admin->getUsername() . "' has updated user settings for user '" . $user->getUsername() . "'";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Settings saved successfully!");
                    return $this->redirect($this->generateUrl('lost_admin_user_list'));
                }
            }
        }

        $roles = $user->getRoles();

        return $this->render('LostAdminBundle:User:edit.html.twig', array(
                    'form' => $form->createView(),
                    'user' => $user,
                    'admin' => $admin,
                    'role' => $roles[0],
                    'changePasswordForm' => $changePasswordForm->createView(),
                    'userSettingform' => $userSettingForm->createView(),
                    'id' => $id
        ));
    }

    /**
     * user login log list
     *
     * @param \Symfony\Component\HttpFoundation\Request $request            
     */
    public function loginLogAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_login_log')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user log detail.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $user = null;
        if ($id) {
            $user = $em->getRepository('LostUserBundle:User')->find($id);
        }
        $query = $em->getRepository('LostUserBundle:UserLoginLog')->getAllUserLoginLogQuery($user, $id);
      /*

        $searchParams = $request->query->all();

        $form = $this->createForm(new LoginLogSearchFormType(), array(
            'searchParams' => isset($searchParams['search']) ? $searchParams['search'] : ''
        ));

        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            $search = $searchParams['search'];

            // set audit log search user log
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Search user login log';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched criteria name:'{$searchParams['search']['search']}', startDate:'{$searchParams['search']['startDate']}' and endDate:'{$searchParams['search']['endDate']}'";

            if ($user) {

                $activityLog['user'] = $user;
            }

            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $query = $em->getRepository('LostUserBundle:UserLoginLog')->getUserLoginLogSearch($query, $searchParams['search']);
        } 
*/
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        return $this->render('LostAdminBundle:User:loginLog.html.twig', array(
                    'pagination' => $pagination,
                    'admin' => $admin,
                   // 'form' => $form->createView(),
                    'id' => $id
        ));
    }
    
    
    public function  loginLogListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0,$id) {
       
        $loginLogColumns = array('Id','Name','IpAddress','ServiceLocation','ActiveServices','AvailableServices','Country','Logintime');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($loginLogColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'l.id';
            $sortOrder = 'ASC';
        } else {
            
            if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'l.id';
            }                   
          if ($gridData['order_by'] == 'Name') {
                
                $orderBy = 'u.username';
            }   
             if ($gridData['order_by'] == 'IpAddress') {
                
                $orderBy = 'l.ipAddress';
            }
          
            if ($gridData['order_by'] == 'ActiveServices') {
                
                $orderBy = 'u.activeServices';
            }
             if ($gridData['order_by'] == 'Country') {
                
                $orderBy = 'c.name';
            }
             if ($gridData['order_by'] == 'Logintime') {
                
                $orderBy = 'l.createdAt';
            } 
              
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('LostUserBundle:UserLoginLog')->getUserLoginLogGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$admin,$id);
        
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
                                       
                    $em = $this->getDoctrine()->getManager();
                    $user = $em->getRepository('LostUserBundle:User')->find($resultRow->getUser()->getID());
                    $userService = $this->get('UserWiseService');
                    $data = $userService->getUserService($resultRow->getIpAddress(), $user);
                    $activeServices = $em->getRepository('LostUserBundle:UserService')->getActiveServices($resultRow->getUser()->getID());
                    
                     $count = 1;
                    $servicesCount = count($activeServices);

                    $activeService = '';
                    if($activeServices){
                        foreach ($activeServices as $service ) {                          
                            
                            if ($count == $servicesCount) {
                                
                                $activeService .= '<span class="btn btn-success btn-sm service">'.$service.'</span>';
                            } else {
                                
                                $activeService .= '<span class="btn btn-success btn-sm service">'.$service.'</span>';
                            }
                            $count++;
                        }
                    }               
                    
                    $acount = 1;
                    $aservicesCount = count($data['services']);

                    $availableServices = '';
                    if($data['services']){
                        foreach ( $data['services'] as $availableservice ) {                          
                            
                            if ($acount == $aservicesCount) {
                                
                                $availableServices .= '<span class="btn btn-success btn-sm service">'.$availableservice.'</span>';
                            } else {
                                
                                $availableServices .= '<span class="btn btn-success btn-sm service">'.$availableservice.'</span>';
                            }
                            $acount++;
                        }
                    }               
                    
                    
                  
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getUser()->getName();
                    $row[] = $resultRow->getIpAddress();
                    $row[] = $data['location'] ; 
                    $row[] = $activeService;
                    //$row[] = '';
                    $row[] = $availableServices;
                    $row[] = $resultRow->getCountry() ? $resultRow->getCountry()->getName():'';
                    $row[] = $resultRow->getCreatedAt()->format('M-d-Y H:i:s');
                     
                   
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function loginLogExportAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_login_log_export')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $user = null;
        if ($id) {
            $user = $em->getRepository('LostUserBundle:User')->find($id);
        }
        $query = $em->getRepository('LostUserBundle:UserLoginLog')->getAllUserLoginLogQuery($user, $id);
        $searchParams = $request->query->all();

        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            $search = $searchParams['search'];

            $query = $em->getRepository('LostUserBundle:UserLoginLog')->getUserLoginLogSearch($query, $searchParams['search']);
        }

        // set audit log search user log
        $activityLog = array();
        $activityLog['admin'] = $admin;

        $desc = "";
        if ($user) {
            $activityLog['user'] = $user;
            $desc = " of user " . $user->getUsername();
        }

        $activityLog['activity'] = 'Export user login log pdf';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has exported Login Log pdf" . $desc;
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        // get total record count
        $result = $query->getQuery()->getResult();
        $limit = count($result);
        $limit = ($limit ? $limit : 1);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, 1, $limit);
        $pdfSavepath = $this->container->get('kernel')->getRootDir() . '/../web/loginLoghistory/';

        // if directory is not available then create directory and give full permission
        if (!is_dir($pdfSavepath)) {
            if (false === @mkdir($pdfSavepath, 0777)) {
                throw new \RuntimeException(sprintf('Unable to create the %s directory', $pdfSavepath));
            }
        }
        $date = date('m-d-Y', time());
        // create pdf file full path
        if ($id) {
            $file_name = 'user_log_' . $user->getUserName() . '_' . $date . '.pdf';
        } else {
            $file_name = 'user_log_all_' . $date . '.pdf';
        }
        $pdfSavepath = $pdfSavepath . $file_name;

        // if exists then delete the file
        if (file_exists($pdfSavepath)) {
            unlink($pdfSavepath);
        }

        // create html to pdf
        $this->get('knp_snappy.pdf')->generateFromHtml($this->renderView('LostAdminBundle:User:loginLogExport.html.twig', array(
                    'pagination' => $pagination
                )), $pdfSavepath);

        // give full permission to the file
        if (file_exists($pdfSavepath)) {
            chmod($pdfSavepath, 0777);
        }

        // get file for download
        $content = file_get_contents($pdfSavepath);
        $response = new Response();

        // set headers
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $file_name . '"');

        $response->setContent($content);
        return $response;
    }

    public function loginLogPrintAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_login_log_print')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $user = null;
        if ($id) {
            $user = $em->getRepository('LostUserBundle:User')->find($id);
        }

        $activityLog = array();
        $activityLog['admin'] = $admin;

        $desc = "";
        if ($user) {
            $activityLog['user'] = $user;
            $desc = " of user " . $user->getUsername();
        }

        $activityLog['activity'] = 'Print user login log';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has print Login Log " . $desc;
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        /* END: add user audit log for user-login-log export */
    }

    public function changeServiceStatusAction($userId, $serviceSettingId) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_service_setting')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to change user services.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('LostUserBundle:User')->find($userId);
        $setting = $em->getRepository('LostUserBundle:UserServiceSetting')->find($serviceSettingId);
        if ($setting) {
            if ($setting->getService()->getName() == 'IPTV') {

                $setting->setServiceStatus(($setting->getServiceStatus() == 'Disabled') ? 'Enabled' : 'Disabled');
                $em->persist($setting);

                // Add User's service log
                $objUserServiceSettingLog = new UserServiceSettingLog();
                $objUserServiceSettingLog->setService($setting->getService());
                $objUserServiceSettingLog->setUser($user);
                $objUserServiceSettingLog->setServiceStatus(($setting->getServiceStatus() == 'Disabled') ? 'Disabled' : 'Reactivated');
                $em->persist($objUserServiceSettingLog);

                $em->flush();

                // Set log user service status change
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $user;
                $activityLog['activity'] = 'User service status change';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has change status of " . $user;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                // #########START##########
                // check selevision api to check whether customer exist in system
                $wsParam['cuLogin'] = $user->getUsername();

                $selevisionService = $this->get('selevisionService');
                $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

                // if customer exists, Disable/Reactivate customer
                if ($wsResponse['status'] == 1) {

                    $wsService = ($setting->getServiceStatus() == 'Disabled') ? 'deactivateCustomer' : 'reactivateCustomer';

                    $wsResponse = $selevisionService->callWSAction($wsService, $wsParam);
                }
                // #########END##########
                $serviceStatus = ($setting->getServiceStatus() == 'Disabled') ? 'Disabled' : 'Reactivated';
                $this->get('session')->getFlashBag()->add('success', "Service " . $setting->getService()->getName() . " " . $serviceStatus . " for user " . $user->getUsername());
            }
        }
        return $this->redirect($this->generateUrl('lost_admin_user_list'));
    }

    public function serviceDetailAction(Request $request, $userId) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_service_setting')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user services.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('LostUserBundle:User')->find($userId);

        if ($this->get('session')->has('packageAdded')) {
            $status = $this->get('session')->get('packageAdded');

            if ($status == 'success') {
                $this->get('session')->getFlashBag()->add('success', "Package added successfully for user.");
            } else if ($status == 'failure') {
                $this->get('session')->getFlashBag()->add('error', $this->get('session')->get('packageError'));
                $this->get('session')->remove('packageError');
            } else {
                $this->get('session')->getFlashBag()->add('notice', "User not found in selevisions system.");
            }
            $this->get('session')->remove('packageAdded');
        }

        $query = $em->getRepository('LostUserBundle:UserService')->getUserPurchaseHistory($userId);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), $this->container->getParameter('record_per_page'));

        return $this->render('LostAdminBundle:User:userDetail.html.twig', array(
                    'pagination' => $pagination,
                    'user' => $user
        ));
    }

    public function addIptvPackageAction($userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_service_setting')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add package.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('LostUserBundle:User')->find($userId);

        $wsParam = array();
        $selevisionService = $this->get('selevisionService');
        $wsRespose = $selevisionService->callWSAction('getAllOffers', $wsParam);

        $packages = array();
        if ($wsRespose['status'] == 1) {

            $packages = $wsRespose;
        }

        $view = array();
        $view['user'] = $user;
        $view['packages'] = $packages;

        return $this->render('LostAdminBundle:User:addIptvPackage.html.twig', $view);
    }

    public function saveIptvPackageAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_service_setting')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to save package.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        if ($request->getMethod() == "POST") {

            $response = array(
                'status' => "failure"
            );

            $em = $this->getDoctrine()->getManager();

            $userId = $request->request->get('userId');
            $packageNameArr = $request->request->get('packageName');
            $packageId = $request->request->get('packageId');

            $user = $em->getRepository('LostUserBundle:User')->find($userId);
            $service = $em->getRepository('LostUserBundle:Service')->findOneBy(array(
                'name' => 'IPTV'
            ));

            // #########START##########
            // check selevision api to check whether customer exist in system
            $wsParam['cuLogin'] = $user->getUsername();

            $selevisionService = $this->get('selevisionService');
            $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

            // if customer exists, update the details
            if ($wsResponse['status'] == 1) {

                // call selevisions service to add package
                $wsParam['offer'] = $packageId;

                $wsResponse = $selevisionService->callWSAction('setCustomerOffer', $wsParam);

                if ($wsResponse['status'] == 1) {

                    $activationDate = new DateTime();
                    $expiryDate = new DateTime();
                    $expiryDate->modify('+1 month');

                    $objUserService = new UserService();
                    $objUserService->setUser($user);
                    $objUserService->setService($service);
                    $objUserService->setPackageId($packageId);
                    $objUserService->setPackageName($packageNameArr[$packageId]);
                    $objUserService->setAmount(0);
                    $objUserService->setActivationDate($activationDate);
                    $objUserService->setExpiryDate($expiryDate);
                    $objUserService->setStatus(1);
                    $em->persist($objUserService);
                    $em->flush();

                    // service added to user
                    $this->get('session')->set('packageAdded', "success");
                } else {
                    // service not added to user
                    $this->get('session')->set('packageAdded', "failure");
                    $this->get('session')->set('packageError', $wsResponse['detail']);
                }
            } else {
                // user not found in selevisions
//                 $em->remove($objUserService);
//                 $em->flush();
                $this->get('session')->set('packageAdded', "UserNotFound");
            }
            // #########END##########
            exit();
        }
    }

    public function removeIptvPackageAction($userId, $packageId) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_service_setting')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to remove package.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('LostUserBundle:User')->find($userId);
        $package = $em->getRepository('LostUserBundle:UserService')->find($packageId);

        // #########START##########
        // check selevision api to check whether customer exist in system
        $wsParam['cuLogin'] = $user->getUsername();

        $selevisionService = $this->get('selevisionService');
        $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

        // if customer exists, update the details
        if ($wsResponse['status'] == 1) {

            // call selevisions service to remove package
            $wsParam['offer'] = $package->getPackageId();
            $wsResponse = $selevisionService->callWSAction('unsetCustomerOffer', $wsParam);

            if ($wsResponse['status'] == 1) {
                $package->setStatus(0);
                $em->persist($package);
                $em->flush();

                // service added to user
                $this->get('session')->getFlashBag()->add('success', "Package removed successfully for user.");
            } else {
                // service not added to user
                $this->get('session')->getFlashBag()->add('error', $wsResponse['detail']);
            }
        } else {
            // user not found in selevisions
//             $em->remove($objUserService);
//             $em->flush();
            $this->get('session')->getFlashBag()->add('notice', "User not found in selevisions system.");
        }
        // #########END##########

        return $this->redirect($this->getRequest()->headers->get('referer'));
    }

    public function purchaseHistoryAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_purchase_history')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user purchase detail.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        $ipAddressZones = $em->getRepository('LostAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

        $query = $em->getRepository('LostServiceBundle:ServicePurchase')->getUserPurchaseHistory(null, $ipAddressZones);
      

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), $this->container->getParameter('record_per_page'));

        return $this->render('LostAdminBundle:User:purchaseHistroy.html.twig', array(
                    'pagination' => $pagination,
                    'admin' => $admin,
                    'currentDate' => date('m-d-Y', time())
        ));
    }
    
    public function purchaseHistoryListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
       
        $purchaseHistoryColumns = array('OrderNumber','Username','Service','Package','PaidAmount','PaymentMethod','TransactionID','PaymentStatus','CompensationValidity','PurchaseDate');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($purchaseHistoryColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'sp.createdAt';
            $sortOrder = 'ASC';
        } else {
            
            if ($gridData['order_by'] == 'OrderNumber') {
                
                $orderBy = 'sp.purchaseOrder';
            }                   
            if ($gridData['order_by'] == 'Username') {
                
                $orderBy = 'u.username';
            }   
             if ($gridData['order_by'] == 'Package') {
                
                $orderBy = 'sp.packageName';
            }
            if ($gridData['order_by'] == 'Service') {
                
                $orderBy = 's.name';
            }
           
            if ($gridData['order_by'] == 'PaidAmount') {
                
                $orderBy = 'sp.payableAmount';
            }
             if ($gridData['order_by'] == 'PaymentMethod') {
                
                $orderBy = 'pm.name';
            }
           if ($gridData['order_by'] == 'TransactionID') {
                
                $orderBy = 'pp.paypalTransactionId';
            } 
              if ($gridData['order_by'] == 'PaymentStatus') {
                
                $orderBy = 'sp.paymentStatus';
            }
              if ($gridData['order_by'] == 'CompensationValidity') {
                
                $orderBy = 'po.compensationValidity';
            }
          
            if ($gridData['order_by'] == 'PurchaseDate') {
                
                $orderBy = 'sp.createdAt';
            }        
            
            
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('LostServiceBundle:ServicePurchase')->getPurchaseHistoryGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        
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
                   
                    $paymentMethodName      = '';
                    $paypalTransactionId    = '';
                    $compensationValidity   = '';
                    $createdAt              = '';
                    
                    if($resultRow->getPurchaseOrder()){

                        if($resultRow->getPurchaseOrder()->getPaymentMethod()){
                            
                            $paymentMethodName = $resultRow->getPurchaseOrder()->getPaymentMethod()->getName();
                        }
                        
                        if($resultRow->getPurchaseOrder()->getPaypalCheckout()){
                            
                            $paypalTransactionId = $resultRow->getPurchaseOrder()->getPaypalCheckout()->getPaypalTransactionId();
                        }
                        
                        $compensationValidity = $resultRow->getPurchaseOrder()->getCompensationValidity();
                        
                        if($resultRow->getPurchaseOrder()->getCreatedAt()){
                            
                            $createdAt = $resultRow->getPurchaseOrder()->getCreatedAt()->format('M-d-Y H:i:s');
                        }
                    }  
                  
                  //  $flagDelete   = 1;
                  
                    $row = array();
                    $row[] = $resultRow->getPurchaseOrder() ? $resultRow->getPurchaseOrder()->getOrderNumber():'';
                    $row[] = $resultRow->getUser() ? $resultRow->getUser()->getUsername():'';
                    $row[] = $resultRow->getService() ? $resultRow->getService()->getName():'';
                    $row[] = $resultRow->getpackageName();
                    $row[] = $resultRow->getPayableAmount();
                    $row[] = $paymentMethodName;
                    $row[] = $paypalTransactionId;                    
                    $row[] = $resultRow->getPaymentStatus();
                    $row[] = $compensationValidity;
                    $row[] = $createdAt;
                   
                   // $row[] = $resultRow->getId().'^'.$flagDelete; 
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_purchase_history_export')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user purchase detail.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $LostLogoImg = $this->getRequest()->getUriForPath('/bundles/Lostuser/images/Lostlogo.jpg'); // Logo web path
        $file_name = 'purchase_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download

        $searchParams = $request->query->all();

        // Get Purchase History Data
        $ipAddressZones = $em->getRepository('LostAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

        $query = $em->getRepository('LostServiceBundle:ServicePurchase')->getUserPurchaseHistory(null, $ipAddressZones);

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf purchase history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export user purchase history";


        if (!empty($searchParams) && isset($searchParams['searchTxt'])) {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched export user purchase history" . json_encode($searchParams['searchTxt']);
            $em->getRepository('LostUserBundle:UserService')->getSearchPurchaseHistory($query, $searchParams['searchTxt']);
        }

        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $result = $query->getQuery()->getResult();

        // create html to pdf
        $pdf = $this->get("white_october.tcpdf")->create();

        // set document information
        $pdf->SetCreator('Lost Portal');
        $pdf->SetAuthor('Lost Portal');
        $pdf->SetTitle('Lost Portal');
        $pdf->SetSubject('Purchase History');

        // set default header data
        $pdf->SetHeaderData('', 0, 'Lost Portal', '<img src="' . $LostLogoImg . '" />');

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('helvetica', '', 9);

        // add a page
        $pdf->AddPage();

        // Load a stylesheet and render html
        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/Lostuser/css/pdf.css');

        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('LostAdminBundle:User:exportPdf.html.twig', array(
            'purchaseData' => $result
        ));

        // output the HTML content
        $pdf->writeHTML($html);

        // reset pointer to the last page
        $pdf->lastPage();

        // Close and output PDF document
        $pdf->Output($file_name, 'D');
        exit();
    }

    public function exportCsvAction(Request $request) {

        //Check permission

        if (!$this->get('admin_permission')->checkPermission('user_purchase_history_export')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user purchase detail.");
            return $this->redirect($this->generateUrl('lost_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $searchParams = $request->query->all();

        // Get Purchase History Data
        $ipAddressZones = $em->getRepository('LostAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

        $query = $em->getRepository('LostServiceBundle:ServicePurchase')->getUserPurchaseHistory(null, $ipAddressZones);

        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv purchase history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv user purchase history";

        if (!empty($searchParams) && isset($searchParams['searchTxt'])) {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched export csv user purchase history" . json_encode($searchParams['searchTxt']);
            $em->getRepository('LostUserBundle:UserService')->getSearchPurchaseHistory($query, $searchParams['searchTxt']);
            
        }

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $result = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function() use($result) {
            
            $handle = fopen('php://output', 'w+');
            
            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array("Order Number", "Username", "Service", "Package", "Amount", "Payment Status", "Purchase Date", "Transaction ID", "Payment Method", "Refund Amount", "Compensation Validity"), ',');
            // Query data from database
            
            foreach ($result as $key => $purchaseHistory) {
                
                $createdAt = '';
                $tranjactionId = '';
                $paymentMethod = '';
                $refundAmount = '';
                $orderNumber = '';
                $compensationValidity = '';
                $paymentStatus = '';
                $username = $purchaseHistory->getUser() ? $purchaseHistory->getUser()->getUsername() : '';
                $packageName = $purchaseHistory->getPackageName() ? $purchaseHistory->getPackageName() : '';
                $payableAmount = $purchaseHistory->getPayableAmount() ? $purchaseHistory->getPayableAmount() : '';

                if ($purchaseHistory->getPurchaseOrder()) {

                    $objcreatedAt = $purchaseHistory->getPurchaseOrder()->getCreatedAt();
                    $createdAt = $objcreatedAt->format('M-d-Y H:i:s');
                    $orderNumber = $purchaseHistory->getPurchaseOrder()->getOrderNumber() ? $purchaseHistory->getPurchaseOrder()->getOrderNumber() : '';
                    $compensationValidity = $purchaseHistory->getPurchaseOrder()->getCompensationValidity() ? $purchaseHistory->getPurchaseOrder()->getCompensationValidity() . ' Hours' : '';
                    $paymentStatus = $purchaseHistory->getPurchaseOrder()->getPaymentStatus() ? $purchaseHistory->getPurchaseOrder()->getPaymentStatus() : '';
                    $paymentMethod = $purchaseHistory->getPurchaseOrder()->getPaymentMethod()->getName() ? $purchaseHistory->getPurchaseOrder()->getPaymentMethod()->getName() : '';
                }

                if ($purchaseHistory->getPurchaseOrder() && $purchaseHistory->getPurchaseOrder()->getPaypalCheckOut()) {

                    $tranjactionId = $purchaseHistory->getPurchaseOrder()->getPaypalCheckOut()->getPaypalTransactionId();
                }

                if ($purchaseHistory->getPurchaseOrder() && $purchaseHistory->getPurchaseOrder()->getRefundAmount() > 0) {

                    $refundAmount = $purchaseHistory->getPurchaseOrder()->getRefundAmount();
                }
    
                fputcsv($handle, array(
                    $orderNumber,
                    $username,
                    $purchaseHistory->getService() ? $purchaseHistory->getService()->getName() : '',
                    $packageName,
                    $payableAmount,
                    $paymentStatus,
                    $createdAt,
                    $tranjactionId,
                    $paymentMethod,
                    $refundAmount,
                    $compensationValidity
                        ), ',');
            }

            fclose($handle);
        });
        
        // create filename
        $file_name = 'purchase_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }

    public function viewAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('LostUserBundle:User')->find($id);

        if (!$user) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user detail.");
            return $this->redirect($this->generateUrl('lost_admin_user_list'));
        }

        // check user mac address how many add maximum
        $userMacAddress = $em->getRepository('LostUserBundle:UserSetting')->findOneBy(array('user' => $user));

        if (isset($userMacAddress) && $userMacAddress && $userMacAddress->getMacAddress()) {

            $request->getSession()->set('maxMacAddress', $userMacAddress->getMacAddress());
        } else {

            $userMacAddress = $em->getRepository('LostAdminBundle:Setting')->findOneBy(array('name' => 'mac_address'));

            $request->getSession()->set('maxMacAddress', $userMacAddress->getValue());
        }

        // get user mac address
        $objMacAddress = $em->getRepository("LostUserBundle:UserMacAddress")->findBy(array('user' => $user, 'isDelete' => 0));

        return $this->render('LostAdminBundle:User:view.html.twig', array('user' => $user, 'userMacAddress' => $objMacAddress));
    }

    public function refundAction(Request $request, $id, $userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('LostUserBundle:User')->find($userId);

        if (!$user) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to refund amount.");
            return $this->redirect($this->generateUrl('lost_admin_user_list'));
        }
        // Set audit log for redund amount
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['user'] = $user;
        $activityLog['activity'] = 'Refund amount';


        //$service = $em->getRepository('LostUserBundle:UserService')->getUserPurchaseHistory($id);
        $service = $em->getRepository('LostUserBundle:UserService')->find($id);

        if ($service) {

            if ($service->getUser() && $user && $service->getUser()->getId() == $user->getId()) {

                $emailAddress = $this->container->getParameter('refund_email_addresses');

                $objDiffrentDays = date_diff($service->getActivationDate(), $service->getExpiryDate());
                $totalDays = $objDiffrentDays->format("%a");
                $oneDayMoney = ($service->getPayableAmount() / $totalDays);

                $objDiffrentDays = date_diff(new \DateTime(), $service->getExpiryDate());
                $remainingDays = $objDiffrentDays->format("%a");

                $refundAmount = number_format(($remainingDays * $oneDayMoney), 2, ".", ".");

                if ($service->getPayableAmount() < $refundAmount) {

                    $this->get('session')->getFlashBag()->add('failure', "You can't refund amount.");
                    return $this->redirect($this->generateUrl('lost_admin_user_list'));
                }

                $body = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:refund.html.twig', array('user' => $user, 'refundAmount' => $refundAmount, 'service' => $service));

                // #########START##########
                // check selevision api to check whether customer exist in system
                $wsParam['cuLogin'] = $user->getUsername();

                $selevisionService = $this->get('selevisionService');
                $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);

                // if customer exists, update the details
                if ($wsResponse['status'] == 1) {

                    // call selevisions service to update account

                    $wsParam['cuLogin'] = $user->getUsername();
                    $wsParam['offer'] = $service->getPackageId();

                    // disable service for the account

                    $wsResponseCustomerOffer = $selevisionService->callWSAction('unsetCustomerOffer', $wsParam);

                    if (!empty($wsResponseCustomerOffer) && $wsResponseCustomerOffer['status'] == 1) {

                        // send email to admin for manual refund
                        foreach ($emailAddress as $key => $value) {

                            $refundEmail = \Swift_Message::newInstance()
                                    ->setSubject("Refund Amount")
                                    ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                                    ->setTo($value)
                                    ->setBody($body->getContent())
                                    ->setContentType('text/html');
                            $this->container->get('mailer')->send($refundEmail);
                        }

                        $service->setExpiryDate(new \Datetime());
                        $service->setRefund(1);
                        $service->setRefundAmount($refundAmount);
                        $em->persist($service);
                        $em->flush();

                        $activityLog['description'] = "Admin " . $admin->getUsername() . " service package refund amount is " . $refundAmount;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $this->get('session')->getFlashBag()->add('success', "Your service packgae amount refund successfully .");
                        return $this->redirect($this->generateUrl('lost_admin_user_list'));
                    } else {

                        $activityLog['description'] = "Admin " . $admin->getUsername() . " service offer does not exit";
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $this->get('session')->getFlashBag()->add('failure', "Service offer does not exits.");
                        return $this->redirect($this->generateUrl('lost_admin_user_list'));
                    }
                } else {

                    $activityLog['description'] = "Admin " . $admin->getUsername() . " you are not authorized to refund payment";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('failure', "You are not authorized to refund payment.");
                    return $this->redirect($this->generateUrl('lost_admin_user_list'));
                }

                // #########END##########
            } else {

                $activityLog['description'] = "Admin " . $admin->getUsername() . " you are not authorized to refund payment";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('failure', "Service is not found in your account.");
                return $this->redirect($this->generateUrl('lost_admin_user_list'));
            }
        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " service is not found active in your account";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $this->get('session')->getFlashBag()->add('failure', "Service is not found active in your account.");
            return $this->redirect($this->generateUrl('lost_admin_user_list'));
        }
    }

    public function getUserServiceDetailAction($userId, $ipAddress) {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('LostUserBundle:User')->find($userId);

        $userService = $this->get('UserWiseService');
        $data = $userService->getUserService($ipAddress, $user);

        $activeServices = $em->getRepository('LostUserBundle:UserService')->getActiveServices($userId);

        return $this->render('LostAdminBundle:User:userServiceLocation.html.twig', array('services' => $data['services'], 'location' => $data['location'], 'activeServices' => $activeServices));
    }

    // add user credit
    public function creditAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $userCreditArr = $request->request->get('userCredit');

        $response = array('status' => 'failure', 'message' => '');

        if ($request->isXmlHttpRequest()) {

            if (!empty($userCreditArr)) {

                $user = $em->getRepository('LostUserBundle:User')->find($userCreditArr['userId']);
                $userCredit = $em->getRepository('LostAdminBundle:Credit')->find($userCreditArr['creditId']);

                if ($userCreditArr['amount'] == '') {

                    $response = array('status' => 'failure', 'message' => 'Please select amount');
                    
                } else {
                    
                    // set purchase order
                    $objPurchaseOrder = new PurchaseOrder();
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setOrderNumber($this->get('PaymentProcess')->generateOrderNumber());
                    $objPurchaseOrder->setTotalAmount($userCreditArr['amount']);
                    $objPurchaseOrder->setPaymentStatus('Completed');
                    $objPurchaseOrder->setSessionId('');
                    $objPurchaseOrder->setPaymentBy('Admin');

                    $em->persist($objPurchaseOrder);
                    $em->flush();

                    $purchaseOrderId = $objPurchaseOrder->getId();

                    // set serice purchase
                    if ($purchaseOrderId) {
                        
                        $objServicePurchase = new ServicePurchase();
                        $objServicePurchase->setUser($user);
                        $objServicePurchase->setCredit($userCredit);
                        $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                        $objServicePurchase->setActualAmount($userCreditArr['amount']);
                        $objServicePurchase->setPayableAmount($userCreditArr['amount']);
                        $objServicePurchase->setPaymentStatus('Completed');
                        $objServicePurchase->setIsCredit(1);
                        $objServicePurchase->setPackageId('');
                        $objServicePurchase->setPackageName('');
                        $objServicePurchase->setSessionId('');

                        $em->persist($objServicePurchase);
                        $em->flush();

                        if ($objServicePurchase->getIsCredit() == 1) {

                            $credit = $objServicePurchase->getCredit()->getCredit();
                            $amount = $objServicePurchase->getPayableAmount();

                            //insert error to failure table
                            $objUserCreditLog = new UserCreditLog();

                            $objUserCreditLog->setUser($user);
                            $objUserCreditLog->setCredit($credit);
                            $objUserCreditLog->setAmount($amount);
                            $objUserCreditLog->setTransactionType('Credit');

                            $em->persist($objUserCreditLog);
                            $em->flush();

                            if ($objUserCreditLog->getId()) {

                                $objUserCredit = $user->getUserCredit();

                                if (!$objUserCredit) {

                                    $objUserCredit = new UserCredit();
                                    $message = 'Credit added successfully.';
                                } else {

                                    $credit = $credit + $objUserCredit->getTotalCredits();
                                    $message = 'Credit updated successfully.';
                                }

                                $objUserCredit->setUser($user);
                                $objUserCredit->setTotalCredits($credit);
                                $em->persist($objUserCredit);
                                $em->flush();

                                if ($objUserCredit->getId()) {

                                    $response = array('status' => 'success', 'message' => $message);
                                }
                            }
                        }
                        else {
                            $response = array('status' => 'failure', 'message' => 'Something went to wrong');
                        }
                    } else {

                        $response = array('status' => 'failure', 'message' => 'Something went to wrong');
                    }
                }
            } else {

                $response = array('status' => 'failure', 'message' => 'Something went to wrong');
            }
        }

        echo json_encode($response);
        exit;
    }

}
