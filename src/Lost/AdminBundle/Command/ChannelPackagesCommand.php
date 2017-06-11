<?php

namespace Lost\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Lost\ServiceBundle\Entity\Package;

class ChannelPackagesCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('Lost:get-channel-packages')
        ->setDescription('Get channel packages from selevision api');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Package Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();

        $selevisionService = $this->getContainer()->get('selevisionService');
        $wsResponse = $selevisionService->callWSAction('getAllOffers');
        
        $offerIds = array();
        if(isset($wsResponse['status']) && !empty($wsResponse['status'])){
            
            if($wsResponse['status'] == 1){
                
                if($wsResponse['data']){
                    
                    foreach ($wsResponse['data'] as $val){
                        
                        $objPackage = $em->getRepository('LostServiceBundle:Package')->findOneBy(array('packageId' => $val->offerId));
                        
                        //Save packages
                        if(!$objPackage){                            
                            $objPackage = new Package();
                        }                    
                        
                        $objPackage->setName($val->offerName);
                        $objPackage->setPackageId($val->offerId);
                        
                        $em->persist($objPackage);
                        $em->flush();
                        $insertIdPackage = $objPackage->getId();

                        $offerIds[] = $val->offerId;
                        
                    }
                    
                    if(!empty($offerIds)){
                        
                        $packageDisabledQuery = $em->createQueryBuilder()->update('LostServiceBundle:Package', 'p')
                                            ->set('p.status', 1)
                                            ->where('p.packageId NOT IN (:packageId)')
                                            ->setParameter('packageId', $offerIds)
                                            ->getQuery()->execute();                        
                    }                    
                }
            }
        }else{
            
            $output->writeln("\n####### Packages not found #######\n");
        }
        
        
        $output->writeln("\n####### End Cron #######\n");
    }
}
