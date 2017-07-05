<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lost\FrontBundle\Entity\Enduserinfo;
use Lost\FrontBundle\Entity\AppLogActivities;

//use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends Controller {

    public function indexAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
        $body = $request->getContent();
        $data = json_decode($body, true);
        $merge_array = array();

        if ($request->getMethod() === 'POST') {

            if (!empty($data)) {

                if (!empty($data['email']) && !empty($data['username']) && !empty($data['password'])) {

                    $objCheckEmail = $em->getRepository('LostFrontBundle:Useraccess')->findOneByEmail($data['email']);
                    $objCheckUserName = $em->getRepository('LostFrontBundle:Useraccess')->findOneByUsername($data['username']);

                    if ((!$objCheckEmail) && (!$objCheckUserName)) {

                        $userManager = $this->get('fos_user.user_manager');
                        $tokenGenerator = $this->container->get('fos_user.util.token_generator');

                        /* Start - Add User */
                        $user = $userManager->createUser();
                        $user->setStatus('Active');
                        $user->setUsertype('Consumer');  // default 'consumer' user
                        $user->setUsername($data['username']);
                        $user->setEmail($data['email']);
                        $user->setPlainPassword($data['password']);
                        //$user->setName($data['name']);
                        $user->setDeviceToken($data['deviceToken']);
                        $user->setDeviceType($data['deviceType']);
                        $user->setIsSignupApp('Y');  // sign up via app 'Y'
                        $user->setEnabled(false);
                        $user->setConfirmationToken($tokenGenerator->generateToken());

                        $group = $em->getRepository('LostFrontBundle:Group')->getGroupByRole('ROLE_USER');
                        $user->addGroup($group);

                        $userManager->updateUser($user);
                        /* End - Add User */

                        /* Start - Add in enduserinfo table */
                        $objCountry = $em->getRepository('LostFrontBundle:Country')->find('226');
                        $phone = (isset($data['phone'])) ? $data['phone']:null;
                        $objEnduserinfo = new Enduserinfo();
                        $objEnduserinfo->setUserid($user);
                        $objEnduserinfo->setCreatedby($user);
                        $objEnduserinfo->setUpdatedby($user);
                        $objEnduserinfo->setCountry($objCountry);
                        $objEnduserinfo->setPhone($phone);
                        $em->persist($objEnduserinfo);
                        $em->flush();
                        /* End - Add in enduserinfo table */

                        $message = 'Thank you for registering with dispensaries.com. Please check your email for the confirmation link.';
                        $status = 'success';

                        $this->notificationMail($user, $data['password']);
						
						/* --- STORE ACTIVITY LOGS [START] ----- */
						$objAppLogActivity = new AppLogActivities();
						$objAppLogActivity->setUserId($user->getId());
						$objAppLogActivity->setActivityType('Registration');
						$objAppLogActivity->setLogDatetime(new \DateTime());
						$em->persist($objAppLogActivity);
						$em->flush();
						/* ---- STORE ACTIVITY LOGS [END] ------ */

                        $merge_array = array(
                            'userId' => $user->getId(),
                            'earnPoints' => 0,
                            'burnPoints' => 0,
                            'referPoints' => 0,
                            'totalPoints' => 0
                        );
                    } elseif ($objCheckUserName) {
                        $message = 'Username already exists.';
                        $status = 'error';
                    } elseif ($objCheckEmail) {
                        $message = 'Email Id already exists.';
                        $status = 'error';
                    }
                } else {
                    $message = "Invalid Data.";
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
            'status' => $status,
        );

        $final_response_data = array_merge($response_data, $merge_array);

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function notificationMail($user = '', $userPassword = '') {

        $em = $this->getDoctrine()->getManager();

        // START CUSTOM EMAIL TEMPALTE
        $objEmailTemplate = $em->getRepository('LostFrontBundle:Emailtemplates')->findOneBy(array('emailkey' => 'ACTIVATION_MAIL', 'status' => 'Active'));

        $logo = $this->container->getParameter('email_logo');
		
        $css = $this->container->getParameter('email_font_css');
        $activation_url = $this->get('router')->generate('dispensaries_front_user_register_confirm', array('token' => $user->getConfirmationToken()), true);
		$activation_url = str_replace(array("app_dev.php/","app_api.php/"),"",$activation_url);
		
        $sitelink = $this->get('router')->generate('dispensaries_front_homepage', array(), true);
		$sitelink = str_replace(array("app_dev.php/","app_api.php/"),"",$sitelink);
		
        $arrSearch = array('##FONT_CSS##', '##LOGO##', '##USER_NAME##', '##PASSWORD##', '##USER_ROLE##', '##ACTIVATION_LINK##', '##SITE_LINK##');
        $arrReplace = array($css, $logo, $user->getUsername(), $userPassword, 'user', $activation_url, $sitelink);
        $body = str_replace($arrSearch, $arrReplace, $objEmailTemplate->getBody());

        $activationRequestEmail = \Swift_Message::newInstance()
                ->setSubject($objEmailTemplate->getSubject())
                ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                ->setTo($user->getEmail())
                ->setBody($body)
                ->setContentType('text/html');
		
        $this->container->get('mailer')->send($activationRequestEmail);

        // Send notification mail to Admin
        $configNotificationObject = $em->getRepository('LostFrontBundle:ConfigurationSettings')->findOneBy(array('configKey' => 'sign_up_dispensaries_team_email'));
        $bccAddress = explode(',', $configNotificationObject->getConfigValue());
        $bccMails = array_map('trim', $bccAddress);
        if (!empty($bccMails)) {
            $objEmailTemplate = $em->getRepository('LostFrontBundle:Emailtemplates')->findOneBy(array('emailkey' => 'SIGNUP_ADMIN_NOTIFICATION', 'status' => 'Active'));

            $logo = $this->container->getParameter('email_logo');
			
            $css = $this->container->getParameter('email_font_css');
            $sitelink = $this->container->get('router')->generate('dispensaries_front_homepage', array(), true);
			$sitelink = str_replace(array("app_dev.php/","app_api.php/"),"",$sitelink);
			
            $activation_url = $this->container->get('router')->generate('dispensaries_front_user_register_confirm', array('token' => $user->getConfirmationToken()), true);
			$activation_url = str_replace(array("app_dev.php/","app_api.php/"),"",$activation_url);
            $sourceType = 'Using App Registration Page';
            $arrSearch = array('##FONT_CSS##', '##LOGO##', '##USER_NAME##', '##USER_TYPE##', '##SITE_LINK##', '##SOURCE_TYPE##');
            $arrReplace = array($css, $logo, $user->getUsername(), $user->getUsertype(), $sitelink, $sourceType);

            $body = str_replace($arrSearch, $arrReplace, $objEmailTemplate->getBody());

            $adminNotificationEmail = \Swift_Message::newInstance()
                    ->setSubject($objEmailTemplate->getSubject())
                    ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                    ->setTo($bccMails)
                    ->setBody($body)
                    ->setContentType('text/html');

            $this->container->get('mailer')->send($adminNotificationEmail);
        }
        return true;
    }

}
