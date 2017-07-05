<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserCheckinListController extends Controller {

    public function checkinListAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $body = $request->getContent();
        $data = json_decode($body, true);
        $merge_array = $receiptList = array();
        $totalCount = 0;
        
        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                $userId = $data['userId'];
                $pageNumber = (isset($data['pageNumber'])) ? $data['pageNumber'] : 1;

                if (!empty($userId)) {

                    $objCheckin = $em->getRepository('LostFrontBundle:Usercheckin')->findBy(array('user_id' => $userId), array('id' => 'DESC'));

                    if ($objCheckin) {

                        if (!empty($pageNumber)) {

                            $paginator = $this->get('knp_paginator');
                            $pagination = $paginator->paginate(
                                    $objCheckin, $pageNumber, $this->container->getParameter('api_default_pagination')
                            );

                            $receiptList = $this->checkInRecords($pagination);
                        } else {
                            $receiptList = $this->checkInRecords($objCheckin);
                        }
                        
                        if (!empty($receiptList)) {                           
                            
                            $message = "";
                            $status = "success";
                            $totalCount = count($objCheckin);
                        } else {
                            $message = "No checkin available.";
                            $status = "error";
                        }
                    } else {
                        $message = "No checkin available.";
                        $status = "error";
                    }
                } else {
                    $message = "Invalid User";
                    $status = "error";
                }
            } else {
                $message = "Invalid Data.";
                $status = "error";
            }
        } else {
            $message = "Invalid Request.";
            $status = "error";
        }

        $response_data = array(
            'message' => $message,
            'status' => $status
        );

        $merge_array = array('receiptList' => $receiptList, 'totalCount' =>$totalCount);
        
        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function checkInRecords($pagination = '') {

        $receiptList = array();

        foreach ($pagination as $checkin) {

            $receiptList[] = array(
                'businessId' => $checkin->getBusinessId()->getId(),
                'businessName' => $checkin->getBusinessId()->getName(),
                'checkinDatetime' => $checkin->getCheckinDatetime()->format('m/d/Y H:i:s')
            );
        }

        return $receiptList;
    }

}
