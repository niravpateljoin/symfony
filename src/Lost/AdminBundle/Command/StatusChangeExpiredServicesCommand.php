<?php

namespace Lost\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class StatusChangeExpiredServicesCommand extends ContainerAwareCommand{
    private $output;
    protected function configure(){

        $this->setName('Lost:status-change-for-expired-services')
        ->setDescription('Change user service status to inactive when services are expired');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start User service status for expired services Cron at ".date('M j H:i')." #######\n");

        // fetch records from service purchase which are expired based on user
        $em = $this->getContainer()->get('doctrine')->getManager();
        $date = new DateTime();
        $query = $em->createQuery("SELECT sp FROM LostServiceBundle:ServicePurchase sp WHERE sp.expiredAt < '".$date->format('Y-m-d H:i:s')."'");
        $expiredServices = $query->execute();
        
        if(count($expiredServices)) {
            foreach ($expiredServices as $expiredService) {
                $serviceId  = $expiredService->getServices()->getId();
                $userId     = $expiredService->getUser()->getId();
                if($userId && $serviceId) {
                    $objUserService = $em->getRepository('LostUserBundle:UserService')->findBy(array('user' => $userId,'service' => $serviceId));
                    
                    foreach($objUserService as $userService) {
                        //echo $userService->getService()->getName();
                        $userService->setStatus(0);
                        $em->persist($userService);
                        $em->flush();
                    }
                    $output->writeln("\n Expired services for service id: ".$serviceId." and User Id: ".$userId." \n");
                }
                //print_r($expiredService);
            }
        } else {
            $output->writeln("\n####### No expired services found #######\n");
        }

        $output->writeln("\n####### End Cron #######\n");
    }
}
