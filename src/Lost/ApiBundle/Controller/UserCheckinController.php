<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\FrontBundle\Entity\Usercheckin;
use Lost\FrontBundle\Entity\Userearnpoints;

class UserCheckinController extends Controller {

    public function checkinAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $body = $request->getContent();
        $data = json_decode($body, true);
        $merge_array = array();

        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                $userId = $data['userId'];
                $encodQRCode = $data['qrcode'];
                $businessId = $data['businessId'];

                if (!empty($userId) && !empty($encodQRCode)) {

                    $checkin_datetime = new \DateTime();
                    $encryptQRCode = base64_decode(base64_decode($encodQRCode));
                    $qrCode = explode('-', $encryptQRCode);

                    if (isset($qrCode['1'])) {

                        $qrCodeBusinessId = (int) $qrCode['1'];

                        //echo "<pre>"; print_r($qrCode); exit;

                        if ($qrCodeBusinessId == (int) $businessId) {

                            //$objBusiness = $em->getRepository('LostFrontBundle:Businessinfo')->findOneBy(array('id' => $qrCodeBusinessId, 'status' => 'Active', 'publishstatus' => 'Y'));
                            //$objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));
                            $objBusiness = $em->getRepository('LostFrontBundle:Businessinfo')->find($qrCodeBusinessId);
                            $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->find($userId);

                            if ($objUserAccess && $objBusiness) {

                                $objLastCheckin = $em->getRepository('LostFrontBundle:Usercheckin')->findOneBy(array('business_id' => $qrCodeBusinessId, 'user_id' => $userId), array('id' => 'DESC'));

                                if ($objLastCheckin) {

                                    $lastCheckinTime = strtotime($objLastCheckin->getCheckinDatetime()->format('Y-m-d H:i:s')); // last checkintime
                                    $dateTwentyFourHoursAgo = strtotime("-24 hours"); // 24 hours ago checkin time

                                    if ($dateTwentyFourHoursAgo >= $lastCheckinTime) {

                                        $this->addCheckin($objUserAccess, $objBusiness, $checkin_datetime); // Add checkin

                                        $this->addUserEarnPoint($objUserAccess, $objBusiness, 'Checkin'); // Add Earn Point

                                        $this->addUserReferalPoint($objUserAccess, $objBusiness); // Add Referall Point

                                        $message = "Checkin has been done successfully.";
                                        $status = "success";
                                    } else {
                                        $message = "You are not allowed to checkin within 24 hours.";
                                        $status = "error";
                                    }
                                } else {
                                    $this->addCheckin($objUserAccess, $objBusiness, $checkin_datetime); // Add checkin 
                                    $this->addUserEarnPoint($objUserAccess, $objBusiness, 'Checkin', 1); // Add Earn Point
                                    $this->addUserReferalPoint($objUserAccess, $objBusiness); // Add Referall Point

                                    $message = "Checkin has been done successfully.";
                                    $status = "success";
                                }
                            } else {
                                $message = "Invalid User or QR code.";
                                $status = "error";
                            }
                        } else {
                            $message = "Invalid QR code or Business.";
                            $status = "error";
                        }
                    } else {
                        $message = "Invalid QR code.";
                        $status = "error";
                    }
                } else {
                    $message = "Invalid User or QR code.";
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

        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function addCheckin($objUserAccess = '', $objBusiness = '', $checkin_datetime = '') {
        $em = $this->getDoctrine()->getManager();

        /* Start - Add Checkin */
        $objUserCheckin = new Usercheckin();

        $objUserCheckin->setUserId($objUserAccess);
        $objUserCheckin->setBusinessId($objBusiness);
        $objUserCheckin->setCheckinDatetime($checkin_datetime);

        $em->persist($objUserCheckin);
        $em->flush();
        /* End - Add Checkin */
    }

    protected function addUserEarnPoint($objUserAccess = '', $objBusiness = '', $type = '', $objCheckinUserAccess = '') {

        $em = $this->getDoctrine()->getManager();
        $is_valid = false;
        $objBusinesspointConfigure = $em->getRepository('LostFrontBundle:BusinesspointConfigure')->findOneBy(array('business_id' => $objBusiness->getId(), 'type' => $type));

        if ($objBusinesspointConfigure) {
            $noOfTranscation = $objBusinesspointConfigure->getNoOfTransaction();
            $points = $objBusinesspointConfigure->getNoOfPoints();
        } else {
            $noOfTranscation = $this->container->getParameter('api_checkin_default_transaction');
            $points = $this->container->getParameter('api_checkin_default_points');
        }

        if ($type == 'Checkin') {

            /* Get Check in no of records */
            $objCheckin = $em->getRepository('LostFrontBundle:Usercheckin')->findBy(array('business_id' => $objBusiness->getId(), 'user_id' => $objUserAccess->getId()), array('id' => 'DESC'));
            $totalCheckinCount = count($objCheckin);
            /* Get Check in Count no of records */

            if (($totalCheckinCount % $noOfTranscation) == 0) { // if no of transaction is 1
                $is_valid = true;
            }
        } else {
            $is_valid = true;
        }

        if ($is_valid) {
            /* Start - User Earn Points */
            $ObjUserearnpoints = new Userearnpoints();

            $ObjUserearnpoints->setUserid($objUserAccess);
            $ObjUserearnpoints->setBusinessId($objBusiness);
            $ObjUserearnpoints->setTotalPoints($points);
            $ObjUserearnpoints->setPointType($type);
            $ObjUserearnpoints->setEarnDatetime(new \DateTime());

            $em->persist($ObjUserearnpoints);
            $em->flush();
            /* End - User Earn Points */

			if ($type == 'Refer') 
			{
				// Send Notification
				if($objCheckinUserAccess)
				{
					if($objCheckinUserAccess->getName() != ''){
						$notification_name = $objCheckinUserAccess->getName();
					} else{
						$notification_name = $objCheckinUserAccess->getUsername();
					}
				}
				else
				{
					$notification_name = '';
				}

				$notificationData = array(
					'userId' => $objUserAccess,
					'businessId' => $objBusiness,
					'deviceType' => $objUserAccess->getDeviceType(),
					'actionType' => 'Refer',
					'actionStatus' => 'Accept',
					'notificationStatus' => 'send',
					'messageContent' => 'Congratulations!!! You have earned referral points by \''.$notification_name.'\'.',
					'otherParameters' => '',
				);
				$objApiCommonHelper = $this->get('dispensaries.helper.common_api');
				$objApiCommonHelper->addPushNotificationData($notificationData, $objUserAccess->getDeviceToken());
			}
			
        }
        return true;
    }

    protected function addUserReferalPoint($objUserAccess = '', $objBusiness = '') {

        $em = $this->getDoctrine()->getManager();

        $objReferal = $em->getRepository('LostFrontBundle:Userreferal')->findBy(array('business_id' => $objBusiness->getId(), 'converted_user_id' => $objUserAccess->getId()), array('id' => 'DESC'));

        if ($objReferal) {
            $allowAccept = true;

            foreach ($objReferal as $referal) {

                if ($referal->getStatus() == 'Pending') {
                    $objReferredBy = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $referal->getReferredBy(), 'usertype' => 'Consumer'));
					
					if($allowAccept)
					{
						$referal->setStatus('Accepted');
					}
					else
					{
						$referal->setStatus('Rejected');
					}
                    
                    $em->persist($referal);
                    $em->flush();
					
					if($allowAccept)
					{
						$this->addUserEarnPoint($objReferredBy, $objBusiness, 'Refer', $objUserAccess); // Add Earn Point
					}

                    $allowAccept = false;
                }
            }
        } else {

            $objReferalNotExist = $em->getRepository('LostFrontBundle:Userreferal')->findBy(array('business_id' => $objBusiness->getId(), 'status' => 'Pending'), array('id' => 'DESC'));

            if ($objReferalNotExist) {

                $allowAccept = true;

                foreach ($objReferalNotExist as $notExistUser) {
					
					if(!$objUserAccess->getEnduserinfo())
						$strEndUserPhone = 'noenduserinfo';
					else
						$strEndUserPhone = substr($objUserAccess->getEnduserinfo()->getPhone(),-10);

                    if (($notExistUser->getType() == 'Email' && $notExistUser->getReferralValue() == $objUserAccess->getEmail()) ||
                            ($notExistUser->getType() == 'Contact' && substr($notExistUser->getReferralValue(),-10) == $strEndUserPhone)
                    ) {
                        if ($notExistUser->getStatus() == 'Pending') {
							
							if($allowAccept)
							{
								$notExistUser->setStatus('Accepted');
							}
							else
							{
								$notExistUser->setStatus('Rejected');
							}
                            
                            $objReferredBy = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $notExistUser->getReferredBy(), 'usertype' => 'Consumer'));
							
							if($allowAccept)
							{
								$this->addUserEarnPoint($objReferredBy, $objBusiness, 'Refer', $objUserAccess); // Add Earn Point
							}
							
							$notExistUser->setConvertedUserId($objUserAccess);
							$em->persist($notExistUser);
							$em->flush();
							$allowAccept = false;
                                
                        }

                       
                    }
                }
            }
        }
    }

}
