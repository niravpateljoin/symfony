<?php

namespace Dispensaries\ApiBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\Container;
use Dispensaries\FrontBundle\Entity\PushNotification;
use RMS\PushNotificationsBundle\Message\AndroidMessage;
use RMS\PushNotificationsBundle\Message\iOSMessage;

class CommonHelper {

    private $entityManager;
    private $container;

    public function __construct(EntityManager $entityManager, Session $session, Container $container) {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->container = $container;
    }

    public function pr($object, $exit = 0) {
        echo "<pre>";
        print_r($object);
        if ($exit == 1)
            exit;
    }

    public function checkPermission($permissionkey) {
        $permitted_actions = $this->session->get('permitted_actions');
        if (in_array($permitted_actions, $haystack)) {
            return true;
        } else {
            return false;
        }
    }

    public function getLatLongFromAddr($address) {

        $arrLattLong = array();
        try {
            $address = str_replace(" ", "+", $address);
            $json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false");
            $json = json_decode($json);
            if ($json->status == 'OK') {
                $arrLattLong['lat'] = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
                $arrLattLong['long'] = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
            }
        } catch (\Exception $e) {
            
        }

        return $arrLattLong;
    }

    public function checkSecureAPI($deviceToken = '', $userId = '') {

        $result = array();
        //echo $deviceToken; exit;
        if (empty($deviceToken) && empty($userId)) {
            $result['status'] = 'error';
            $result['message'] = 'Invalid Data.';
        }

        $objUseraccess = $this->entityManager->getRepository('DispensariesFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'apiToken' => $deviceToken));

        if ($objUseraccess) {
            $result['status'] = 'success';
            $result['message'] = '';
        } else {
            $result['status'] = 'error';
            $result['message'] = 'Invalid Data.';
        }


        return $result;
    }

    /* Calculate  requested burn point amount */

    public function calculateBurnPoint($objBusinessPointConfig = '', $burnPoints = '') {

        if ($objBusinessPointConfig) {
            //$noOfTranscation = $objBusinessPointConfig->getNoOfTransaction();
            $noOfPoints = $objBusinessPointConfig->getNoOfPoints();
            $amount = $objBusinessPointConfig->getAmounts();
        } else {
            $noOfPoints = $this->container->getParameter('api_burn_default_points');
            $amount = $this->container->getParameter('api_burn_default_amount');
        }

        $total_burn_amount = round(($amount * $burnPoints) / $noOfPoints);

        return $total_burn_amount;
    }

    /* Calculate Earn points */

    public function calculateEarnPoint($objBusinessPointConfig = '', $transactionAmount = '') {

        if ($objBusinessPointConfig) {
            //$noOfTranscation = $objBusinessPointConfig->getNoOfTransaction();
            $noOfPoints = $objBusinessPointConfig->getNoOfPoints();
            $amount = $objBusinessPointConfig->getAmounts();
        } else {
            $noOfPoints = $this->container->getParameter('api_transaction_default_points');
            $amount = $this->container->getParameter('api_transaction_default_amount');
        }

        $total_earn_point = round(($transactionAmount * $noOfPoints) / $amount);

        return $total_earn_point;
    }

    // start - add data in push notification table
    public function addPushNotificationData($data = array(), $deviceToken = '') {

        if (!empty($data)) {

            $userId = ($data['userId']) ? $data['userId'] : null;
            $businessId = ($data['businessId']) ? $data['businessId'] : null;
            $deviceType = ($data['deviceType']) ? $data['deviceType'] : null;
            $actionType = ($data['actionType']) ? $data['actionType'] : null;
            $actionStatus = ($data['actionStatus']) ? $data['actionStatus'] : null;
            $notificationStatus = ($data['notificationStatus']) ? $data['notificationStatus'] : null;
            $messageContent = ($data['messageContent']) ? $data['messageContent'] : null;
            $otherParameters = ($data['otherParameters']) ? $data['otherParameters'] : null;
            //$createdAt          = ($data['createdAt']) ? new \DateTime() : null;


            $objPushNotification = new PushNotification();

            $objPushNotification->setUserId($userId);
            $objPushNotification->setBusinessId($businessId);
            $objPushNotification->setDeviceType($deviceType);
            $objPushNotification->setActionType($actionType);
            $objPushNotification->setActionStatus($actionStatus);
            $objPushNotification->setNotificationStatus($notificationStatus);
            $objPushNotification->setMessageContent($messageContent);
            $objPushNotification->setOtherParameters($otherParameters);
            $objPushNotification->setCreatedAt(new \DateTime());

            $this->entityManager->persist($objPushNotification);
            $this->entityManager->flush();

            // Start - Send Notification on device
            $contentData = array(
                'deviceType' => $deviceType,
                'message' => $messageContent,
                'type' => $actionType,
            );

            $this->pushNotificationInDevice($contentData, $deviceToken); // Push notifiction for device
        }
    }

    // Start - send notifiction to device
    public function pushNotificationInDevice($contentData = array(), $deviceToken = '') {

        if (!empty($contentData) && (!empty($deviceToken))) {

            if ($contentData['deviceType'] == 'Android') {

                //$deviceToken = 'fR6xTfXjXo8:APA91bGkGc_MgBd98JOt48f_Z-4Sh0dBHxgFDGT3iuCHtiWctqWLsB1xwltQvXx_Mdst-tD5CLnP2jbTQXduGYBnr6oiz-DclGinYtUBBUQ1wegz6JAMz36B2tC8MDqSTPvw-A1orux9';

                unset($contentData['deviceType']);
                
                 $newContentData = array(
                    'notificationType' => $contentData['type']  
                );
                 
                $message = new AndroidMessage();
                $message->setGCM(true);
                $message->setMessage($contentData['message']);
                $message->setData($newContentData);
                $message->setDeviceIdentifier($deviceToken);
                $this->container->get('rms_push_notifications')->send($message);
                //echo "Done"; exit;
                return true;
            }

            if ($contentData['deviceType'] == 'iOS') {

                //$deviceToken = 'e992a7487701d56ff8d15c55059124bec9246d1867a656e701d94616c1adf127';
                //$deviceToken = 'f2c118c98a2529e2e21463b048c7f4d1b116bfd92bb2317d4109fc934523889e';

                unset($contentData['deviceType']);

                $newContentData = array(
                    'notificationType' => $contentData['type']  
                );
                
                $message = new iOSMessage();
                $message->setMessage($contentData['message']);
                $message->setData($newContentData);
                $message->setDeviceIdentifier($deviceToken);

                $this->container->get('rms_push_notifications')->send($message);
                //echo "DOne"; exit;
                return true;
            }
        }
        return true;
    }
	
	function getDistance( $latitude1, $longitude1, $latitude2, $longitude2 ) 
	{  
		$earth_radius = 6371;

		$dLat = deg2rad( $latitude2 - $latitude1 );  
		$dLon = deg2rad( $longitude2 - $longitude1 );  

		$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);  
		$c = 2 * asin(sqrt($a));  
		$d = $earth_radius * $c;  

		return $d;  
	}

}