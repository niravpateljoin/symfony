<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserPointsController extends Controller {

    public function pointAction(Request $request) {

        $message = '';
        $status = '';
        $merge_array = array();

        if ($request->getMethod() === 'POST') {

            $em = $this->getDoctrine()->getManager();
            $body = $request->getContent();
            $data = json_decode($body, true);

            if (!empty($data)) {

                $userId = $data['userId'];
                $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

                if ($objUserAccess) {

                    //$totalReciptPointData = $em->getRepository('LostFrontBundle:Userreceipt')->getTotalEarnPoints($userId);
                    
                    $totalEarnPoints = $em->getRepository('LostFrontBundle:Userearnpoints')->getTotalEarnPoints($userId);
                    $totalReferPoints = $em->getRepository('LostFrontBundle:Userearnpoints')->getTotalEarnPoints($userId,'','Refer');
                    $totalCheckPoints = $em->getRepository('LostFrontBundle:Userearnpoints')->getTotalEarnPoints($userId,'','Checkin');
                    $totalReceiptPoints = $em->getRepository('LostFrontBundle:Userearnpoints')->getTotalEarnPoints($userId,'','Receipt');
                    
                    $merge_array = array(
                        'userId' => $objUserAccess->getId(),
                        'checkinPoints' => (!empty($totalCheckPoints)) ? $totalCheckPoints : 0,
                        'referPoints' => (!empty($totalReferPoints)) ? $totalReferPoints : 0,
                        'uploadPoints' => (!empty($totalReceiptPoints)) ? $totalReceiptPoints : 0,
                        'totalPoints' => (!empty($totalEarnPoints)) ? $totalEarnPoints : 0
                    );
                    
                    $message = "";
                    $status = "success";
                } else {
                    $message = "Invalid user.";
                    $status = "error";
                }
            } else {
                $message = "Invalid data.";
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

}
