<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Lost\UserBundle\Entity\UserActivityLog;

/**
 * 
 */
class PurchaseHistoryController extends Controller {

    // User purchase history
    public function purchaseHistoryAction(Request $request) {
        
        $user = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        
        //$query = $em->getRepository('LostServiceBundle:ServicePurchase')->findBy(array('user' => $user, 'paymentStatus' => 'completed'));
        //$query = $em->getRepository('LostUserBundle:UserService')->getUserPurchaseHistory($user);
        
        
        $query = $em->getRepository('LostServiceBundle:ServicePurchase')->getUserPurchaseHistory($user,null);
        
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
                $query, $request->query->get('page', 1), $this->container->getParameter('record_per_page'));
        
       
        return $this->render('LostUserBundle:PurchaseHistory:purchaseHistory.html.twig', array('pagination' => $pagination));        
    }
    
    public function purchaseHistoryJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
    
        $request  = $this->getRequest();
        $user     = $this->get('security.context')->getToken()->getUser();
        $em       = $this->getDoctrine()->getManager();
        $helper   = $this->get('grid_helper_function');
    
        $aColumns = array('id', 'orderNumber', 'totalAmount', 'refundAmount', 'paymentMethod', 'paymentStatus', 'type', 'service', 'purchaseDate');
        
        $gridData = $helper->getSearchData($aColumns);
    
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
    
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
    
            $orderBy = 'po.id';
            $sortOrder = 'DESC';
    
        } else {
    
            if ($gridData['order_by'] == 'orderNumber') {
    
                $orderBy = 'po.orderNumber';
            }    
            
            if ($gridData['order_by'] == 'purchaseDate') {
                
                $orderBy = 'po.createdAt';
            }
            
        }
    
        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
    
        $data  = $em->getRepository('LostServiceBundle:PurchaseOrder')->getPurchaseHistoryGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $user);
    
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
                    
                    if($resultRow->getServicePurchases()){
                        
                        foreach ($resultRow->getServicePurchases() as $servicePurchase){
                            
                            if($servicePurchase->getIsCredit()){
                                
                            }
                        }
                        
                    }
                    
                    $row = array();
                    $row[] = '';
                    $row[] = $resultRow->getOrderNumber();
                    $row[] = ($resultRow->getTotalAmount())?"$".$resultRow->getTotalAmount():'';
                    $row[] = ($resultRow->getRefundAmount())?"$".$resultRow->getRefundAmount():'';
                    $row[] = ($resultRow->getPaymentMethod())?$resultRow->getPaymentMethod()->getName():'';
                    $row[] = $resultRow->getPaymentStatus();
                    $row[] = '';
                    $row[] = '';
                    $row[] = ($resultRow->getCreatedAt())?$resultRow->getCreatedAt()->format('M-d-Y H:i:s'):'';
    
                    $output['aaData'][] = $row;
                }
            }
        }
    
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
    
        return $response;
    }
    
    public function exportpdfAction(Request $request) {
    
        $user = $this->get('security.context')->getToken()->getUser(); //Login User Object
        $em = $this->getDoctrine()->getManager(); //Entity Manager
        
        //Check Email Verified
        if (!$user->getIsEmailVerified()) {
    
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $token = $tokenGenerator->generateToken();
            $user->setConfirmationToken($token);
            $user->setEmailVerificationDate(new \DateTime());
    
            $em->persist($user);
            $em->flush();
        }
    
        //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user;
        $activityLog['activity']     = 'Export PDF';
        $activityLog['description']  = "User '".$user->getUsername()."' has export pdf for purchase history.";
    
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        //End here
    
        $rootDirPath = $this->container->get('kernel')->getRootDir(); //Get Application Root DIR path
        $LostLogoImg = $this->getRequest()->getUriForPath('/bundles/Lostuser/images/Lostlogo.jpg'); //Logo web path
        $file_name = 'purchase_'.$user->getUserName().'_'.date('m-d-Y', time()).'.pdf';// Create pdf file name for download
    
        //Get Purchase History Data
        $query = $em->getRepository('LostServiceBundle:ServicePurchase')->getUserPurchaseHistory($user,null);
        $result = $query->getQuery()->getResult();
    
        // create html to pdf
        $pdf = $this->get("white_october.tcpdf")->create();
    
        // set document information
        $pdf->SetCreator('Lost Portal');
        $pdf->SetAuthor('Lost Portal');
        $pdf->SetTitle('Lost Portal');
        $pdf->SetSubject('Purchase History');
    
        // set default header data
        $pdf->SetHeaderData('', 0, 'Lost Portal', '<img src="'.$LostLogoImg.'" />');
    
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
    
        # Load a stylesheet and render html
        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/Lostuser/css/pdf.css');
                $html = '<style>'.$stylesheet.'</style>';
                $html .= $this->renderView('LostUserBundle:PurchaseHistory:exportPdf.html.twig',
                        array('purchaseData' => $result)
                );
                        // output the HTML content
                        $pdf->writeHTML($html);
                        // reset pointer to the last page
                $pdf->lastPage();
                //Close and output PDF document
                $pdf->Output($file_name, 'D');
                exit;
    }
}
