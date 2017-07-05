<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserEarnPointsController extends Controller {

    public function earnPointsAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $body = $request->getContent();
        $data = json_decode($body, true);

        $earnPointsData = array();
        $userId = $data['userId'];
        $businessId = $data['businessId'];
        $pageNumber = (isset($data['pageNumber'])) ? $data['pageNumber'] : 1;
        $totalCount = $totalEarnPoints = 0;

        if (!empty($userId) && !empty($businessId)) {

            $objBusiness = $em->getRepository('LostFrontBundle:Businessinfo')->findOneBy(array('id' => $businessId, 'status' => 'Active', 'publishstatus' => 'Y'));
            $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

            if ($objBusiness && $objUserAccess) {
                
                /* Start - User Earn Points */
                $objUserEarnPoint = $em->getRepository('LostFrontBundle:Userearnpoints')->findBy(array('userid' => $userId, 'business_id' => $businessId),array('earn_datetime'=>'DESC'));

                if ($objUserEarnPoint) {

                    if (!empty($pageNumber)) {

                        $paginator = $this->get('knp_paginator');
                        $pagination = $paginator->paginate(
                                $objUserEarnPoint, $pageNumber, $this->container->getParameter('api_default_pagination')
                        );

                        $earnPointsData = $this->earnPointsRecords($pagination);
                    } else {
                        $earnPointsData = $this->earnPointsRecords($objUserEarnPoint);
                    }

                    if (!empty($earnPointsData)) {

                        $businessEarnPoints = $em->getRepository('LostFrontBundle:Userearnpoints')->getTotalEarnPoints($userId, $businessId);
                        $businessBurnPoints = $em->getRepository('LostFrontBundle:Userburnpoints')->getTotalBurnPoints($userId, $businessId, 'Accepted');

                        $finalTotalEarnPoints = $businessEarnPoints - $businessBurnPoints;
                        $message = "";
                        $status = "success";
                        $totalCount = count($objUserEarnPoint);
                        $totalEarnPoints = $finalTotalEarnPoints;
                        
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
            /* End - User Earn Points */
        } else {
            $message = "Invalid data.";
            $status = "error";
        }

        $merge_array = array(
            'earnPoints' => $earnPointsData,
            'totalCount' => $totalCount,
            'totalEarnPoints' => $totalEarnPoints
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

    protected function earnPointsRecords($pagination = '') {
        $earnPointsData = array();
        foreach ($pagination as $earnPoints) {

            $earnPointsData[] = array(
                'type' => $earnPoints->getPointType(),
                'points' => $earnPoints->getTotalPoints(),
                'time' => $earnPoints->getEarnDatetime()->format('m/d/Y H:i:s'),
            );
        }

        return $earnPointsData;
    }
	
	function getAvailablePointsToRedeemAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();

        $body = $request->getContent();
        $data = json_decode($body, true);

        $earnPointsData = array();
        $userId = $data['userId'];
        $businessId = $data['businessId'];
		
		$businessEarnPoints = $em->getRepository('LostFrontBundle:Userearnpoints')->getTotalEarnPoints($userId, $businessId);
		$businessBurnPoints = $em->getRepository('LostFrontBundle:Userburnpoints')->getTotalBurnPoints($userId, $businessId, 'Accepted,Requested');
		
		//echo $businessEarnPoints."=".$businessBurnPoints;die;
        $finalTotalEarnPoints = $businessEarnPoints - $businessBurnPoints;
		
		if($finalTotalEarnPoints < 0)
			$finalTotalEarnPoints = 0;
		
		$objBusinessPointConfig = $em->getRepository('LostFrontBundle:BusinesspointConfigure')->findOneBy(array('business_id' => $businessId, 'type' => 'Burn'));

		if($objBusinessPointConfig)
		{
			$intBurnDefaultPoints = $objBusinessPointConfig->getNoOfPoints();
			$intBurnDefaultAmount = $objBusinessPointConfig->getAmounts();
		}
		else
		{
			$intBurnDefaultPoints = $this->container->getParameter('api_burn_default_points');
			$intBurnDefaultAmount = $this->container->getParameter('api_burn_default_amount');
		}
		
		$strRedeemBurnPointMessage = $intBurnDefaultPoints." point(s) = $".$intBurnDefaultAmount;
				
		$intReferPoints = ($objBusinessPointConfig) ? $objBusinessPointConfig->getNoOfPoints() : $this->container->getParameter('api_refer_default_points');
		
		$arrData = array(
							'totalAvailablePointsToRedeem' => $finalTotalEarnPoints,
							'redeemBurnPointsMessage' => $strRedeemBurnPointMessage
						);
		
		$response = new Response(json_encode($arrData));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
		
	}

}
