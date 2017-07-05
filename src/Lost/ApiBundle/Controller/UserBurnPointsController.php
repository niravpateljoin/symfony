<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserBurnPointsController extends Controller {

    public function burnPointsAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $body = $request->getContent();
        $data = json_decode($body, true);
        $burnPointsData = array();
        $userId = $data['userId'];
        $businessId = $data['businessId'];
        $pageNumber = (isset($data['pageNumber'])) ? $data['pageNumber'] : 1;
        $totalCount = 0;

        if (!empty($userId) && !empty($businessId)) {

            $objBusiness = $em->getRepository('LostFrontBundle:Businessinfo')->findOneBy(array('id' => $businessId, 'status' => 'Active', 'publishstatus' => 'Y'));
            $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

            if ($objBusiness && $objUserAccess) {
                /* Start - User Burn Points */
                $objUserBurnPoint = $em->getRepository('LostFrontBundle:Userburnpoints')->findBy(array('userid' => $userId, 'business_id' => $businessId, 'status' => array('Accepted')),array('requested_datetime'=>'DESC'));

                if ($objUserBurnPoint) {

                    if (!empty($pageNumber)) {

                        $paginator = $this->get('knp_paginator');
                        $pagination = $paginator->paginate(
                                $objUserBurnPoint, $pageNumber, $this->container->getParameter('api_default_pagination')
                        );

                        $burnPointsData = $this->burnPointsRecords($pagination);
                    } else {
                        $burnPointsData = $this->burnPointsRecords($objUserBurnPoint);
                    }

                    if (!empty($burnPointsData)) {
                        $message = "";
                        $status = "success";
                        $totalCount = count($objUserBurnPoint);
                    } else {
                        $message = "No records found.";
                        $status = "error";
                    }
                } else {
                    $message = "No records found.";
                    $status = "error";
                }
            } else {
                $message = "Invalid business or user found.";
                $status = "error";
            }
            /* End - User Burn Points */
        } else {
            $message = "Invalid data.";
            $status = "error";
        }

        $merge_array = array(
            'burnPoints' => $burnPointsData,
            'totalCount' => $totalCount
        );

        $response_data = array(
            'message' => $message,
            'status' => $status
        );

        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function burnPointsRecords($pagination = '') {

        $burnPointsData = array();

        foreach ($pagination as $burnPoints) {

            $burnPointsData[] = array(
                'type' => $burnPoints->getStatus(),
                'points' => $burnPoints->getTotalBurnedPointsRequested(),
                'amount' => $burnPoints->getRequestedAmount(),
                'time' => $burnPoints->getBurnDatetime()->format('m/d/Y H:i:s'),
                //'burnBy' => (!empty($burnPoints->getBurnedBy()->getName())) ? $burnPoints->getBurnedBy()->getName() : $burnPoints->getBurnedBy()->getUsername()
                'burnBy' => ($burnPoints->getBurnedBy()->getName()!='') ? $burnPoints->getBurnedBy()->getName(): $burnPoints->getBurnedBy()->getUsername()
            );
        }

        return $burnPointsData;
    }

}
