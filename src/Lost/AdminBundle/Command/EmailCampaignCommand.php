<?php

namespace Lost\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class EmailCampaignCommand extends ContainerAwareCommand{
    private $output;
    protected function configure(){

        $this->setName('Lost:send-email-campaign')
        ->setDescription('Blast marketing or support email to customer.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("\n####### Start Send Email Campaign Cron at ".date('M j H:i')." #######\n");

        // fetch records from email_campaign which are under process
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $emailCampaignRecord = $em->getRepository('LostUserBundle:EmailCampaign')->findBy(array('emailStatus' => 'Sending'));
        
        // new cron execute will not start until old will not be completed.
        if( ! count($emailCampaignRecord)) {

            $objEmailCampaign = $em->getRepository('LostUserBundle:EmailCampaign')->findBy(array('emailStatus' => 'Active'));
            
            if(count($objEmailCampaign)) {

                foreach($objEmailCampaign as $emailCampaign) {
                    
                    $serviceIds = array();
                    foreach ($emailCampaign->getServices() as $service) {
                    
                        $serviceIds[] = $service->getId();
                    }
                    
                    $serviceLocationIp = array();
                    $sentUserId = array();
                    $i = 0;
                    if($emailCampaign->getServiceLocations()){
                        
                        foreach ($emailCampaign->getServiceLocations() as $serviceLocation) {
                            
                            if($serviceLocation->getIpAddressZones()){
                                
                                foreach ($serviceLocation->getIpAddressZones() as $ipAddress){
                                    
                                    $fromIP = ip2long($ipAddress->getFromIpAddress());
                                    $toIP   = ip2long($ipAddress->getToIpAddress());
                                    
                                    $servicePurchaseUser = $em->getRepository('LostUserBundle:UserService')->getServicePurchasedUsers($serviceIds, $fromIP, $toIP);
                                    
                                    if($servicePurchaseUser){
                                        
                                        // update email status 'Sending'
                                        $emailCampaign->setEmailStatus('Sending');
                                        $em->persist($emailCampaign);
                                        $em->flush();
                                        
                                        foreach ($servicePurchaseUser as $purchasedService){
                                            
                                            $userId = $purchasedService->getUser()->getId();
                                            
                                            $sendMailFlag = false;
                                            
                                            if(!in_array($userId,$sentUserId)){
                                                
                                                $sentUserId[] = $userId;
                                                
                                                if ($emailCampaign->getEmailType() == 'M') {
                                                
                                                    if (!$purchasedService->getUser()->getIsEmailOptout()) {
                                                        
                                                        $sendMailFlag = true;
                                                    }
                                                } else {
    
                                                    $sendMailFlag = true;
                                                }
                                            }
                                            
                                            if($sendMailFlag){
                                                
                                                $subject   = $emailCampaign->getSubject();
                                                $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                                                $toEmail   = $purchasedService->getUser()->getEmail();
                                                $body      = $this->getContainer()->get('templating')->renderResponse('LostAdminBundle:Emails:email_campaign.html.twig', array('username' => $purchasedService->getUser()->getFirstname(), 'emailCampaign' => $emailCampaign));
                                                
                                                $emailCampaignmail = \Swift_Message::newInstance()
                                                                ->setSubject($subject)
                                                                ->setFrom($fromEmail)
                                                                ->setTo($toEmail)
                                                                ->setBody($body->getContent())
                                                                ->setContentType('text/html');
                                                
                                                if($this->getContainer()->get('mailer')->send($emailCampaignmail)){

                                                    $output->writeln("\nCampaign Email sent to : ".$toEmail." \n");
                                                }else{
                                                    
                                                    $output->writeln("\nCampaign Email sending failed to : ".$toEmail." \n");
                                                }
                                            }
                                        }
                                        
                                        $emailCampaign->setEmailStatus('Sent');
                                        $em->persist($emailCampaign);
                                        $em->flush();
                                    }
                                }
                            }                            
                        }                    
                    }else{
                        
                        $output->writeln("\n####### Mail send service location not found. #######\n");
                    }
                }
            }else{

                $output->writeln("\n####### Active email campaign not found. #######\n");
            }
        }else{
            $output->writeln("\n####### New Cron execution will not start until old cron process complete #######\n");
        }

        $output->writeln("\n####### End Cron #######\n");
    }
}
