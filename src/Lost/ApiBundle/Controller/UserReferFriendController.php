<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\FrontBundle\Entity\Userreferal;

#use Vresh\TwilioBundle;

class UserReferFriendController extends Controller {

    public function referFriendAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        //$hostUrl = $this->container->getParameter('hostUrl');
        $body = $request->getContent();
        $data = json_decode($body, true);

        $merge_array = array();
        //echo "<pre>"; print_r($data); exit;       

        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                $userId = $data['userId'];
                $businessId = $data['businessId'];
                $email = $data['email'];
                $phoneNo = $data['contactNo'];
                $referred_datetime = new \DateTime();

                if (!empty($userId) && !empty($businessId) && (!empty($email) || !empty($phoneNo))) {

                    $ref_val = ($email != '') ? explode(',', $email) : explode(',', $phoneNo);
                    $type = ($email != '') ? 'Email' : 'Contact';

                    $objBusiness = $em->getRepository('LostFrontBundle:Businessinfo')->findOneBy(array('id' => $businessId, 'status' => 'Active', 'publishstatus' => 'Y'));
                    $objUserAccess = $em->getRepository('LostFrontBundle:Useraccess')->findOneBy(array('id' => $userId, 'usertype' => 'Consumer'));

                    if ($objBusiness && $objUserAccess) {

                        $is_valid = true;
                        foreach ($ref_val as $referValue) {
                            if ($type == 'Email') {
                                if (filter_var($referValue, FILTER_VALIDATE_EMAIL) === false) {
                                    $is_valid = false;
                                    break;
                                }
                            }
                        }

                        //echo "<prE>"; print_r($is_valid); exit;
                        if ($is_valid) 
						{   
							$arrAlreadyReferred = array();
							$arrReferredSuccess = array();
							$intRecordIndex = 0;
							$arrReferredEmail = array();
							$arrReferredContact = array();
                            foreach ($ref_val as $referValue) 
							{
								if($intRecordIndex < 10)
								{
									if($type == 'Email'){
									   $objCheckRef = $em->getRepository('LostFrontBundle:Useraccess')->findOneByEmail($referValue); 
									   
									   $objCheckExistRef = $em->getRepository('LostFrontBundle:Userreferal')->findOneBy(array('type' => 'Email','referral_value' => $referValue, 'referred_by' => $userId, 'business_id' => $businessId)); 
									}
									
									if($type == 'Contact'){
										//$objCheckRef = $em->getRepository('LostFrontBundle:Enduserinfo')->findOneByPhone($referValue);
										$arrPhoneUserId = $em->getRepository('LostFrontBundle:Enduserinfo')->getUserPhoneExist($referValue);
										
										if(count($arrPhoneUserId) > 0)
											$intPhoneUserId = $arrPhoneUserId[0]['id'];
										else
											$intPhoneUserId = '';
										
										$objCheckRef = $em->getRepository('LostFrontBundle:Enduserinfo')->findOneBy(array("userid" => $intPhoneUserId));
										
										$objCheckRef = ($objCheckRef) ? $objCheckRef->getUserid():'';
										
										$objCheckExistRef = $em->getRepository('LostFrontBundle:Userreferal')->findOneBy(array('type' => 'Contact','referral_value' => $referValue, 'referred_by' => $userId, 'business_id' => $businessId)); 
									}
									
									if(!$objCheckExistRef)
									{
										$objUserReferal = new Userreferal();

										$objUserReferal->setType($type);
										$objUserReferal->setReferralValue($referValue);
										$objUserReferal->setBusinessId($objBusiness);
										$objUserReferal->setReferredBy($objUserAccess);
										
										if($objCheckRef){
											$objUserReferal->setConvertedUserId($objCheckRef);
										}
										
										$objUserReferal->setReferredDatetime($referred_datetime);

										$em->persist($objUserReferal);
										
										$arrReferredSuccess[] = $referValue;
										
										if($type == 'Email')
										{
											$arrReferredEmail[] = $referValue;
										}
										else
										{
											$arrReferredContact[] = $referValue;
										}
										
									}
									else
									{
										$arrAlreadyReferred[] = $referValue;
									}
								}
								
								$intRecordIndex++;
                            }
                            
							$messageResult = array();
                            if (count($arrReferredEmail) > 0) {
                                $messageResult = $this->senMail(@implode(",",$arrReferredEmail), $objBusiness, $objUserAccess); // send mail
                            }

                             if (count($arrReferredContact) > 0) {
                                $messageResult = $this->sendSms(@implode(",",$arrReferredContact), $objBusiness, $objUserAccess); // send sms
                            }
							
							if(count($messageResult) == 0)
							{
								$messageResult['status'] = 'success';
								$messageResult['message'] = '';
							}
							
                            if ($messageResult['status'] != 'success') {
                                $message = $messageResult['message'];
                                $status = "error";
                            } else {
                                
                                $em->flush();
                                
								$arrMessage = array();
								if(count($arrReferredSuccess) > 0)
								{
									$arrMessage[] = "Following ".$type." referred successfully: ".@implode(", ",$arrReferredSuccess);
									$status = "success";
								}
								
								if(count($arrAlreadyReferred) > 0)
								{
									$arrMessage[] = "Following ".$type." is already referred by you: ".@implode(", ",$arrAlreadyReferred);
									$status = "error";
								}
								
								$message = @implode("\n\n",$arrMessage);
                            }
                        } else {
                            $message = "Invalid email ids.";
                            $status = "error";
                        }
                    } else {
                        $message = "Invalid business or user.";
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

    /* send Mail */

    public function senMail($email = '', $objBusiness = '', $objUserAccess = '') {

        $final_Emails = explode(',', $email);
        //echo "<pre>"; print_r($final_Emails); exit;
        $em = $this->getDoctrine()->getManager();

        // START CUSTOM EMAIL TEMPALTE
        $objEmailTemplate = $em->getRepository('LostFrontBundle:Emailtemplates')->findOneBy(array('emailkey' => 'REFER_BUSINESS', 'status' => 'Active'));

        $logo = $this->container->getParameter('email_logo');
        $css = $this->container->getParameter('email_font_css');

        $sitelink = $this->get('router')->generate('dispensaries_front_homepage', array(), true);
		$sitelink = str_replace(array("app_dev.php/","app_api.php/"),"",$sitelink);
		
        $view_url = $this->generateUrl('dispensaries_front_business_view', array('id' => $objBusiness->getSlug()),true);
		$view_url = str_replace(array("app_dev.php/","app_api.php/"),"",$view_url);

        //echo "<pre>"; print_r($view_url); exit;
        $userName = ($objUserAccess->getName() != '') ? $objUserAccess->getName() : $objUserAccess->getUsername();
        $arrSearch = array('##FONT_CSS##', '##LOGO##', '##BUSINESS_NAME##', '##BUSINESS_VIEW_LINK##', '##SITE_LINK##', '##BUSINESS_TYPE##', '##USER_NAME##');
        $arrReplace = array($css, $logo, $objBusiness->getName(), $view_url, $sitelink, ucfirst($objBusiness->getType()), $userName);

        $body = str_replace($arrSearch, $arrReplace, $objEmailTemplate->getBody());

        $activationRequestEmail = \Swift_Message::newInstance()
                ->setSubject($objEmailTemplate->getSubject())
                ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                ->setTo($final_Emails)
                ->setBody($body)
                ->setContentType('text/html');

        $this->container->get('mailer')->send($activationRequestEmail);
        $returnValue['status'] = 'success';
        $returnValue['message'] = 'Mail has been sent.';

        return $returnValue;
    }

    /* send SMS */

    public function sendSms($phoneNo = '', $objBusiness = '', $objUserAccess = '') {

        $final_phone_nos = explode(',', $phoneNo);
        //echo "<pre>"; print_r($final_phone_nos); exit;
        if (!$this->container->hasParameter('twilio_account_sid') || $this->container->getParameter('twilio_account_sid') == '' ||
                !$this->container->hasParameter('twilio_auth_token') || $this->container->getParameter('twilio_auth_token') == '' ||
                !$this->container->hasParameter('twilio_from_number') || $this->container->getParameter('twilio_from_number') == '' ||
                $phoneNo == '') {

            $returnValue['status'] = 'error';
            $returnValue['message'] = 'Twilio account has not set properly yet. Verification sms will not sent to user.';

            return $returnValue;
        }

        $account_sid = $this->container->getParameter('twilio_account_sid');
        $auth_token = $this->container->getParameter('twilio_auth_token');
        $client = new \Services_Twilio($account_sid, $auth_token);

        $businesName = $objBusiness->getName();
        $userName = ($objUserAccess->getName() != '') ? $objUserAccess->getName() : $objUserAccess->getUsername();

        foreach ($final_phone_nos as $strPhone) {

            try {
				
				if(strpos($strPhone,"+") !== false)
				{
					$phone = $strPhone;
				}
				else
				{
					$phone = '+1'.substr($strPhone,-10);
				}

                $message = $client->account->messages->create(array(
                    "From" => '+' . $this->container->getParameter('twilio_from_number'),
                    "To" => '+' . $phone,
                    "Body" => $userName . ' has referred to you. Download the Lost.com app and checkin for \'' . $businesName . '\' business and earn loyalty points.'
                ));

                $returnValue['status'] = 'success';
                $returnValue['message'] = '';
            } catch (\Services_Twilio_RestException $e) {

                $returnValue['status'] = $e->getStatus();
                $returnValue['message'] = $e->getMessage();
                break;
            }
        }

        return $returnValue;
        //echo "<pre>";print_r($returnValue); exit;
    }

}
