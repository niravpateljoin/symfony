<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#use Lost\FrontBundle\Entity\Userreceipt;

class UserReceiptListController extends Controller {

    public function receiptsAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $body = $request->getContent();
        $data = json_decode($body, true);

        $merge_array = $receiptList = array();
        $pageNumber = (isset($data['pageNumber'])) ? $data['pageNumber'] : 1;
        $totalCount = $totalEarnPoint = 0;
        //echo "<pre>"; print_r($data); exit;
        //
            
        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                $userId = $data['userId'];
                $businessId = $data['businessId'];
                
                $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

                if ($objUserAccess) {

                    if (empty($businessId)) {
                        
                        $objReceipt = $em->getRepository('LostFrontBundle:Userreceipt')->findBy(array('user_id' => $userId),array('uploaded_datetime'=>'DESC'));
                        $totalEarnPointData = $em->getRepository('LostFrontBundle:Userreceipt')->getTotalEarnPoints($userId);
                    } else {
                        $objReceipt = $em->getRepository('LostFrontBundle:Userreceipt')->findBy(array('user_id' => $userId, 'business_id' => $businessId),array('uploaded_datetime'=>'DESC'));
                        $totalEarnPointData = $em->getRepository('LostFrontBundle:Userreceipt')->getTotalEarnPoints($userId, $businessId);
                    }

                    if ($objReceipt) {

                        if (!empty($pageNumber)) {

                            $paginator = $this->get('knp_paginator');
                            $pagination = $paginator->paginate(
                                    $objReceipt, $pageNumber, $this->container->getParameter('api_default_pagination')
                            );

                            $receiptList = $this->receiptRecords($pagination);
                        } else {
                            $receiptList = $this->receiptRecords($objReceipt);
                        }

                        if (!empty($receiptList)) {

                            $message = "";
                            $status = "success";
                            $totalCount = count($objReceipt);
                            $totalEarnPoint = $totalEarnPointData;

                        } else {
                            $message = "No receipts are available.";
                            $status = "error";
                        }
                    } else {
                        $message = "No receipts are available.";
                        $status = "error";
                    }
                } else {
                    $message = "Invalid user.";
                    $status = "error";
                }
            } else {
                $message = "Invalid data.";
                $status = "error";
            }
        } else {
            $message = "Invalid request.";
            $status = "error";
        }

        $response_data = array(
            'message' => $message,
            'status' => $status
        );


        $merge_array = array('receiptList' => $receiptList, 'totalCount' => $totalCount, 'totalEarnPoints' => $totalEarnPoint);
        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function receiptRecords($pagination = '') {

        $receiptList = array();
        //$totalEarnPoints = 0;
        $hostUrl = $this->container->getParameter('hostUrl');

        foreach ($pagination as $receipt) {
            $receiptList[] = array(
                'receiptNumber' => $receipt->getReceiptNumber(),
                'receiptDate' => $receipt->getReceiptDate()->format('m/d/Y'),
                'receiptPoints' => $receipt->getEarnedPoint(),
                'trancationValue' => $receipt->getTransactionValue(),
                'uploadedDate' => $receipt->getUploadedDatetime()->format('m/d/Y H:i:s'),
                'currentStatus' => $receipt->getStatus(),
                'receiptImage' => $hostUrl . '/uploads/business/receipt/' . $receipt->getId() . '/' . $receipt->getReceiptImage()
            );
        }

        return $receiptList;
    }

}
