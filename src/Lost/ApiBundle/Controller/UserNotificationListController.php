<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#use Lost\FrontBundle\Entity\Userreceipt;

class UserNotificationListController extends Controller {

    public function notificationListAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $body = $request->getContent();
        $data = json_decode($body, true);

        $merge_array = $pushNotificationList = array();
        $pageNumber = (isset($data['pageNumber'])) ? $data['pageNumber'] : 1;
        $totalCount = $totalEarnPoint = 0;

        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                $userId = $data['userId'];

                $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

                if ($objUserAccess) {

                    $objPushNotification = $em->getRepository('LostFrontBundle:PushNotification')->findBy(array('user_id' => $userId), array('created_at' => 'DESC'));

                    if ($objPushNotification) {

                        if (!empty($pageNumber)) {

                            $paginator = $this->get('knp_paginator');
                            $pagination = $paginator->paginate(
                                    $objPushNotification, $pageNumber, $this->container->getParameter('api_default_pagination')
                            );
                            $pushNotificationList = $this->pushNotificationRecords($pagination);
                        } else {
                            $pushNotificationList = $this->pushNotificationRecords($objPushNotification);
                        }

                        if (!empty($pushNotificationList)) {

                            $message = "";
                            $status = "success";
                            $totalCount = count($objPushNotification);
                        } else {
                            $message = "No notifications are available.";
                            $status = "error";
                        }
                    } else {
                        $message = "No notifications are available.";
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


        $merge_array = array('pushNotificationList' => $pushNotificationList, 'totalCount' => $totalCount);
        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function pushNotificationRecords($pagination = '') {

        $receiptList = array();
        $hostUrl = $this->container->getParameter('hostUrl');

        foreach ($pagination as $notificationList) {
            $receiptList[] = array(
                'notificationType' => $notificationList->getActionType(),
                'notificationMessage' => $notificationList->getMessageContent(),
                'notificationDate' => $notificationList->getCreatedAt()->format('m/d/Y H:i:s')
            );
        }

        return $receiptList;
    }

}
