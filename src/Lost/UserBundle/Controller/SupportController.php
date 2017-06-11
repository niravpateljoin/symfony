<?php

namespace Lost\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use FOS\UserBundle\Mailer\MailerInterface;
use Lost\UserBundle\Entity\Support;
use Lost\UserBundle\Entity\SupportCategory;
use Lost\UserBundle\Form\Type\SupportFormType;
use Symfony\Component\Validator\Constraints\NotBlank;

class SupportController extends Controller {

    public function supportAction(Request $request) {
        
        $em = $this->getDoctrine()->getManager();
        
        if ($this->container->get('kernel')->getEnvironment() == 'test') {
            $form = $this->createFormBuilder(array())
            ->add('firstname','text')
            ->add('lastname','text')
            ->add('email', 'email')
            ->add('number','text')
            ->add('category', 'entity', array(
                  'class' => 'LostUserBundle:SupportCategory',
                  'empty_value' => 'Support Category',
                  'property' => 'name'))
            ->add('services', 'entity', array(
                  'class' => 'LostUserBundle:Service',
                  'empty_value' => 'Support Service',
                  'property' => 'name'))
            ->add('location', 'entity', array(
                  'class' => 'LostUserBundle:SupportLocation',
                  'empty_value' => 'Location',
                  'property' => 'name'))
            ->add('time','text')
            ->add('message','textarea')
            ->getForm();
        } else {
        $form = $this->createFormBuilder(array())
                ->add('firstname','text')
                ->add('lastname','text')
                ->add('email', 'email')
                ->add('number','text')
                ->add('category', 'entity', array(
                    'class' => 'LostUserBundle:SupportCategory',
                    'empty_value' => 'Support Category',
                    'property' => 'name'))
                ->add('services', 'entity', array(
                    'class' => 'LostUserBundle:Service',
                    'empty_value' => 'Support Service',
                    'property' => 'name'))
                ->add('location', 'entity', array(
                    'class' => 'LostUserBundle:SupportLocation',
                    'empty_value' => 'Location',
                    'property' => 'name'))
                ->add('time','text')
                ->add('message','textarea')
                ->add('captcha', 'captcha', array('invalid_message' => 'Invalid CAPTCHA','as_url' => true, 'reload' => true,'error_bubbling'=>true,))
                ->getForm();   
        }
                
        //Ajax Form Post Request
        if($request->isXmlHttpRequest()) {
            
            if ($request->getMethod() == "POST") {
                
                $form->handleRequest($request);
                $data = $form->getData();
                
                $erroFlg = false;
                $errMsg = array();
                $jsonResponse = array();
                $jsonResponse['process'] = '';
                $jsonResponse['error']   = '';
                $jsonResponse['mailSend']= '';
                
                if (!$form->isValid()) {
                    
                    if($form->getErrors()){
                        foreach ($form->getErrors() as $key => $val){
                            
                            if($key == 0){
                                
                                $errMsg[]['captcha'] = $val->getMessageTemplate();
                            }
                        }
                        $erroFlg = true;
                    }                    
                }
                             
                if($data['firstname'] == ''){
                    
                    $errMsg[]['firstname'] = 'Please enter first name.';
                }
                if($data['lastname'] == ''){
                    
                    $errMsg[]['lastname'] = 'Please enter last name.';
                }
                if($data['email'] == ''){
                    
                    $errMsg[]['email'] = 'Please enter email.';
                }
                if($data['number'] == ''){
                    
                    $errMsg[]['number'] = 'Please enter telephone or mobile number.';
                }
                if($data['category'] == ''){
                    
                    $errMsg[]['category'] = 'Please select category.';
                }
                if($data['services'] == ''){
                    
                    $errMsg[]['services'] = 'Please select service.';
                }
                if($data['location'] == ''){
                    
                    $errMsg[]['location'] = 'Please select location.';
                }
                if($data['time'] == ''){
                    
                    $errMsg[]['time'] = 'Please enter time available.';
                }
                if($data['message'] == ''){
                    
                    $errMsg[]['message'] = 'Please enter message.';
                }
                
                
                if(isset($errMsg) && !empty($errMsg)){
                    $erroFlg = true;
                }
                
                
                if (!$erroFlg) {
                    
                    $categoryData = $em->getRepository('LostUserBundle:SupportCategory')->find($data['category']);
                    $locationData = $em->getRepository('LostUserBundle:SupportLocation')->find($data['location']);
                    $providerData = $em->getRepository('LostUserBundle:Service')->find($data['services']);
                    
                    $data['category'] = ($categoryData)?$categoryData->getName():'';
                    $data['services']  = ($providerData)?$providerData->getName():'';
                    $data['location'] = ($locationData)?$locationData->getName():'';
                    
                    // sent user mail to support team
                    $bodySupportTeamEmail = $this->container->get('templating')->renderResponse('LostUserBundle:Emails:support_team_email.html.twig', array('data' => $data));
                    
                    
                    $sendSupportTeamMail = \Swift_Message::newInstance()
                            ->setSubject('Portal Support: '.$categoryData->getName())
                            ->setFrom($data['email'])
                            ->setTo($this->container->getParameter('support_email_recipient'))
                            ->setBody($bodySupportTeamEmail->getContent())
                            ->setContentType('text/html');
                    
                    if($this->container->get('mailer')->send($sendSupportTeamMail)){
                        
                        $jsonResponse['process'] = 'Success';
                        $jsonResponse['mailSend']= 'Success';
                    }else{
                        $jsonResponse['process'] = 'Success';
                        $jsonResponse['mailSend']= 'Failed';
                    }
                    
                }else{
                    
                    $jsonResponse['process'] = 'Failed';
                    $jsonResponse['error']   = $errMsg;                    
                }
            }
            
            echo json_encode($jsonResponse);
            exit;
        }else {
            
            return $this->render('LostUserBundle:Support:support.html.twig', array(
                    'form' => $form->createView(),
                    //'user' => $user
            ));
        }        
    }
    
    public function indexAction(Request $request) {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objSupport = new Support();
        $form = $this->createForm(new SupportFormType($user), $objSupport);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                $formData = $form->getData();
                $formData = $objSupport->setUser($user);
                $em->persist($formData);
                $em->flush();

                // sent user mail to support team
                $bodySupportEmail = $this->container->get('templating')->renderResponse('LostUserBundle:Support:email_user_support.html.twig', array('username' => $user, 'message' => $objSupport->getDescription()));

                $support_email_verification = \Swift_Message::newInstance()
                        ->setSubject($formData->getSubject())
                        ->setFrom($user->getEmail())
                        ->setTo($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                        ->setBody($bodySupportEmail->getContent())
                        ->setContentType('text/html');

                $this->container->get('mailer')->send($support_email_verification);
                
                // sent mail support team to user

                $bodyUserEmail = $this->container->get('templating')->renderResponse('LostUserBundle:Support:email_support.html.twig', array('username' => $user, 'ticket' => $objSupport->getId()));

                $user_email_verification = \Swift_Message::newInstance()
                        ->setSubject("Ticket Lost".$objSupport->getId() . ' ' . $formData->getSubject())
                        ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                        ->setTo($user->getEmail())
                        ->setBody($bodyUserEmail->getContent())
                        ->setContentType('text/html');

                $this->container->get('mailer')->send($user_email_verification);
                
                $this->get('session')->getFlashBag()->add('success', 'Your message was sent successfully');
                return $this->redirect($this->generateUrl('lost_user_support'));
            }
        }


        return $this->render('LostUserBundle:Support:index.html.twig', array(
                    'form' => $form->createView(),
                    'user' => $user
        ));
    }
}
