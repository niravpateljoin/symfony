<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#use Lost\FrontBundle\Entity\Userreceipt;

class UserReferalListController extends Controller {

    public function referalListAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $hostUrl = $this->container->getParameter('hostUrl');
        $body = $request->getContent();
        $data = json_decode($body, true);

        $merge_array = $referalList = array();
        $totalCount = 0;
        // echo "<pre>"; print_r($data); exit;

        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                $userId = $data['userId'];
                $pageNumber = (isset($data['pageNumber'])) ? $data['pageNumber'] : 1;

                $objReferal = $em->getRepository('LostFrontBundle:Userreferal')->findBy(array('referred_by' => $userId), array('referred_datetime' => 'DESC'));

                if ($objReferal) {

                    if (!empty($pageNumber)) {

                        $paginator = $this->get('knp_paginator');
                        $pagination = $paginator->paginate(
                                $objReferal, $pageNumber, $this->container->getParameter('api_default_pagination')
                        );

                        $referalList = $this->referalRecords($pagination);
                    } else {
                        $referalList = $this->referalRecords($objReferal);
                    }

                    if (!empty($referalList)) {

                        $message = "";
                        $status = "success";
                        $totalCount = count($objReferal);
                    } else {
                        $message = "No referrals are available.";
                        $status = "error";
                    }
                } else {
                    $message = "No referrals are available.";
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
        
        $merge_array = array('referalList' => $referalList, 'totalCount' => $totalCount);
        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function referalRecords($pagination = '') {

        $referalList = array();

        foreach ($pagination as $referal) {
            $referalList[] = array(
                'businessId' => $referal->getBusinessId()->getId(),
                'businessName' => $referal->getBusinessId()->getName(),
                'referralType' => $referal->getType(),
                'referralValue' => $referal->getReferralValue(),
                'referredDatetime' => $referal->getReferredDatetime()->format('m/d/Y H:i:s'),
                'referralStatus' => $referal->getStatus()
            );
        }

        return $referalList;
    }

}
