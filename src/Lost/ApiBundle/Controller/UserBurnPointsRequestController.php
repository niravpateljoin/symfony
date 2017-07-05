<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\FrontBundle\Entity\Userburnpoints;

class UserBurnPointsRequestController extends Controller {

    public function burnPointsRequestAction(Request $request) {

        //echo $request->headers->get('userId'); exit;
        $em = $this->getDoctrine()->getManager();

        $body = $request->getContent();
        $data = json_decode($body, true);
        $merge_array = array();


        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                //echo $request->attribute->get('version'); exit;
                $businessId = $data['businessId'];
                $userId = $data['userId'];
                $burnPoints = $data['burnPoints'];
                $burn_datetime = new \DateTime();

                //echo "<pre>"; print_r($uploaded_datetime); exit;
                if (!empty($businessId) && !empty($userId) && !empty($burnPoints)) {

                    $objBusiness = $em->getRepository('LostFrontBundle:Businessinfo')->findOneBy(array('id' => $businessId, 'status' => 'Active', 'publishstatus' => 'Y'));
                    $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

                    if ($objBusiness && $objUserAccess) {

                        $userTotalEarnPoints = $em->getRepository('LostFrontBundle:Userearnpoints')->getTotalEarnPoints($userId, '', '');
                        $userTotalBurnPoints = $em->getRepository('LostFrontBundle:Userburnpoints')->getTotalBurnPoints($userId, '', 'Accepted');
                        $finalTotalEarnPoints = $userTotalEarnPoints - $userTotalBurnPoints;

                        if ($burnPoints <= $finalTotalEarnPoints) {

                            $objBusinessPointConfig = $em->getRepository('LostFrontBundle:BusinesspointConfigure')->findOneBy(array('business_id' => $businessId, 'type' => 'Burn'));

                            $objApiCommonHelper = $this->get('dispensaries.helper.common_api');
                            $total_amount = $objApiCommonHelper->calculateBurnPoint($objBusinessPointConfig, $burnPoints);

                            $objUserBurnPoints = new Userburnpoints();

                            $objUserBurnPoints->setUserid($objUserAccess);
                            $objUserBurnPoints->setBusinessId($objBusiness);
                            $objUserBurnPoints->setTotalBurnedPointsRequested($burnPoints);
                            //$objUserBurnPoints->setBurnDatetime($burn_datetime);
                            $objUserBurnPoints->setRequestedDatetime($burn_datetime);
                            $objUserBurnPoints->setRequestedAmount($total_amount);
                            $objUserBurnPoints->setStatus('Requested');

                            $em->persist($objUserBurnPoints);
                            $em->flush();

                            $message = "Burn point has been requested successfully.";
                            $status = "success";
                        } else {
                            $message = "Requested burn point can not be greater than total points.";
                            $status = "error";
                        }
                    } else {
                        $message = "Invalid business or user found.";
                        $status = "error";
                    }
                } else {
                    $message = "Invalid data.";
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

        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
