<?php

namespace Lost\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Lost\ServiceBundle\Entity\Package;

class PackageInactiveCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('Lost:inactive-packages')
        ->setDescription('Inactive expired packages from custome account');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Package Inactive Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();

        $objUserService = $em->getRepository('LostUserBundle:UserService')->getExpiredPackage();
        
        if($objUserService){
            
            foreach ($objUserService as $activeService){
                
                $userName  = $activeService->getUser()->getUserName();
                $packageId = $activeService->getPackageId();
                $userId    = $activeService->getUser()->getId();
                $packageName = $activeService->getPackageName();
                
                $wsParam = array();
                $wsParam['cuLogin'] = $userName;
                $wsParam['offer']   = $packageId;
                
                $selevisionService = $this->getContainer()->get('selevisionService');
                $wsResponse = $selevisionService->callWSAction('unsetCustomerOffer',$wsParam);
                
                if(isset($wsResponse['status']) && !empty($wsResponse['status'])){
                
                    if($wsResponse['status'] == 1){
                                         
                        $activeService->setStatus(0);
                        $em->persist($activeService);
                        $em->flush();
                        
                        $output->writeln("\n####### UserId: ".$userId." AND PackageName: ".$packageName." has been inactive #######\n");
                    }
                }else{
                    
                    $output->writeln("\n####### ".json_encode($wsResponse)." #######\n");
                }
            }
        }else{
            
            $output->writeln("\n####### Expired package not found. #######\n");
        }
        
        $output->writeln("\n####### End Cron #######\n");                        
    }
}
