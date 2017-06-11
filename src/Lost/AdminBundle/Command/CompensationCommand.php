<?php

namespace Lost\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Lost\UserBundle\Entity\UserService;
use Lost\ServiceBundle\Entity\ServicePurchase;
use Lost\UserBundle\Entity\Compensation;
use Lost\ServiceBundle\Controller\SelevisionController;
use Lost\UserBundle\Entity\CustomerCompensationLog;
use Lost\ServiceBundle\Entity\PurchaseOrder;


class CompensationCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('Lost:compensation')->setDescription('Get compensation of services');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Compensation Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $objCompensation = $em->getRepository('LostUserBundle:Compensation')->findOneBy(array('status' => 'Inprogress', 'isActive' => true));
        
        if($objCompensation) {
            
            $output->writeln("\n Cron already in progress\n");
            $output->writeln("\n####### End Cron #######\n");
            exit;
            
        } else {
            
            // get compansation recrod
            $records = $em->getRepository('LostUserBundle:Compensation')->findBy(array('status' => 'Queued', 'isActive' => true));
            
            if($records) {
                
                foreach($records as $record) {
                    
                    // to prevent another cron, set status to inprogress
                    $record->setStatus('Inprogress');
                    $em->persist($record);
                    $em->flush();
                    
                    $compensationService = array();
                    $serviceNameArr = array();
                    
                    if($record->getServices()){
                        
                        foreach ($record->getServices() as $service){
                            
                            $serviceNameArr[] = strtoupper($service->getName());
                        }
                        
                        //Customer wise compentions                    
                        if($record->getType() == 'Customer'){
                            
                            $output->writeln("\n####### Customer wise compenstion process #######\n");
                            
                            $this->customerWiseCompensation($record,$serviceNameArr,$output);                                                                                
                        }
                        
                        //ServiceLocation wise compentions
                        if($record->getType() == 'ServiceLocation'){
                        
                            $output->writeln("\n####### Service Location wise compenstion process #######\n");
                            
                            $this->serviceLocationWiseCompensation($record,$serviceNameArr,$output);
                        }
                    }

                    //once done, set status to completed
                    $record->setStatus('Completed');
                    $em->persist($record);
                    $em->flush();
                }
            }
        }
        
        $output->writeln("\n####### End Cron #######\n");
    }
    
    public function customerWiseCompensation($record,$serviceNameArr,$output){
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        if($record->getUsers()) {
        
            foreach($record->getUsers() as $customer) {
                
                $isServiceExtended = false;
                $output->writeln("\n####### Compensation process for user ".$customer->getUserName()." #######\n");
                
                $activeUserService = $em->getRepository("LostUserBundle:UserService")->getUserActiveServiceForCompensation($serviceNameArr,$customer->getId());
                
                if($activeUserService){
                
                    foreach ($activeUserService['data'] as $key => $objUserActiveService){
                        
                        if($key){
                            
                            if(strtoupper($key) == 'IPTV'){
                                
                                $isIPTVAutoExtend = false;
                                if($activeUserService['autoExtendService'] == 'IPTV'){

                                    $isIPTVAutoExtend = true;
                                }
                        
                                $output->writeln("\n####### User ".$customer->getUserName()." IPTV service extended process inprocess  #######\n");
                                if($this->processCompensationOnIPTV($record,$customer,$objUserActiveService,$isIPTVAutoExtend)){
                        
                                    $isServiceExtended = true;
                        
                                    $output->writeln("\n####### User ".$customer->getUserName()." IPTV service extended process successfully completed #######\n");
                                }else{
                                    
                                    $output->writeln("\n####### User ".$customer->getUserName()." IPTV service extended not completed #######\n");
                                }
                            }
                        
                            if(strtoupper($key) == 'ISP'){
                                
                                $isISPAutoExtend = false;
                                if($activeUserService['autoExtendService'] == 'ISP'){
                                
                                    $isISPAutoExtend = true;
                                }
                        
                                $output->writeln("\n####### User ".$customer->getUserName()." ISP service extended process inprocess  #######\n");
                                if($this->processCompensationOnISP($record,$customer,$objUserActiveService,$isISPAutoExtend)){
                        
                                    $isServiceExtended = true;
                        
                                    $output->writeln("\n####### ISP service extended successfully of ".$customer->getUserName()." #######\n");
                                }else{
                                    
                                    $output->writeln("\n####### User ".$customer->getUserName()." ISP service extended not completed #######\n");
                                }
                            }    
                        }          
                    }                                  
                }
                
                
                if($isServiceExtended){
                
                    if($record->getIsEmailActive() == 1) {
                
                        if($this->sendCompensationMail($record,$customer)){
                
                            $output->writeln("\n####### Compensation Email has been sent to ".$customer->getEmail()." #######\n");
                        }else{
                
                            $output->writeln("\n####### Compensation Email sending failed to ".$customer->getEmail()." #######\n");
                        }
                    }
                }
            }
        }
    }
    
    public function serviceLocationWiseCompensation($record,$serviceNameArr,$output){
        
        $em = $this->getContainer()->get('doctrine')->getManager();
    
        if($record->getServiceLocations()){
        
            // Get service locations
            foreach($record->getServiceLocations() as $serviceLocation) {
        
                // get ip address zones
                foreach($serviceLocation->getIpAddressZones() as $ipAddressZone) {
        
                    if($ipAddressZone->getFromIpAddress() != "" && $ipAddressZone->getToIpAddress() != "") {
        
                        $objUsers = $em->getRepository("LostUserBundle:User")->getAllCustomerIpAddressZoneWise($ipAddressZone->getFromIpAddressLong(), $ipAddressZone->getToIpAddressLong());
        
                        if($objUsers) {
        
                            foreach($objUsers as $customer) {
                                
                                $isServiceExtended = false;
                                
                                $activeUserService = $em->getRepository("LostUserBundle:UserService")->getUserActiveServiceForCompensation($serviceNameArr,$customer->getId());
                                
                                if($activeUserService){
//                 echo "<pre>"; print_r($activeUserService['data']);exit;
                                    foreach ($activeUserService['data'] as $key => $objUserActiveService){
                        
                                        if($key){
        
                                            if(strtoupper($key) == 'IPTV'){
                                                
                                                $isIPTVAutoExtend = false;
                                                if($activeUserService['autoExtendService'] == 'IPTV'){
                                                
                                                    $isIPTVAutoExtend = true;
                                                }
                                                
                                                $output->writeln("\n####### User ".$customer->getUserName()." IPTV service extended process inprocess  #######\n");
                                                if($this->processCompensationOnIPTV($record,$customer,$objUserActiveService,$isIPTVAutoExtend)){
    
                                                    $isServiceExtended = true;
                                                    
                                                    $output->writeln("\n####### IPTV service extended successfully of ".$customer->getUserName()." #######\n");                                                    
                                                }else{
                                                    
                                                    $output->writeln("\n####### User ".$customer->getUserName()." IPTV service extended not completed #######\n");
                                                }
                                            }
                                            
                                            if(strtoupper($key) == 'ISP'){
                                                
                                                $isISPAutoExtend = false;
                                                if($activeUserService['autoExtendService'] == 'ISP'){
                                                
                                                    $isISPAutoExtend = true;
                                                }
                                            
                                                $output->writeln("\n####### User ".$customer->getUserName()." ISP service extended process inprocess  #######\n");
                                                if($this->processCompensationOnISP($record,$customer,$objUserActiveService,$isISPAutoExtend)){
                                                    
                                                    $isServiceExtended = true;
                                                    
                                                    $output->writeln("\n####### ISP service extended successfully of ".$customer->getUserName()." #######\n");
                                                }else{
                                                    
                                                    $output->writeln("\n####### User ".$customer->getUserName()." ISP service extended not completed #######\n");
                                                }
                                            }
                                        }
                                    }
                                }    
                                
                                if($isServiceExtended){
                                    
                                    if($record->getIsEmailActive() == 1) {
                                        
                                        if($this->sendCompensationMail($record,$customer)){
                                            
                                            $output->writeln("\n####### Compensation Email has been sent to ".$customer->getEmail()." #######\n");
                                        }else{
                                            
                                            $output->writeln("\n####### Compensation Email sending failed to ".$customer->getEmail()." #######\n");
                                        }
                                    }
                                }                                
                            }
                        }
                    }
                }
            }
        }
    }
    
    public function processCompensationOnIPTV($objCompensation,$objCustomer,$objUserActiveService,$autoExtend = false){
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        if($objCompensation && $objCustomer && $objUserActiveService){
            
            $totalIPTVExtendDays = 0;
            if($objCompensation->getIptvDays()){

                $totalIPTVExtendDays = $objCompensation->getIptvDays();
            }else{

                if($autoExtend){
                                    
                    $totalIPTVExtendDays = $this->convertHoursIntoDays($objCompensation->getIspHours());
                }
            }
            
            if($totalIPTVExtendDays){
            
        
                $compensationStatus = 'Failure';
                $apiError = '';
                
                $wsParam = array();
                $wsParam['cuLogin'] = $objCustomer->getUsername();
                $wsParam['offer']   = $objUserActiveService->getPackageId();
                $wsParam['bonus']   = $objCompensation->getIptvDays();
                
                // call selevision services giveCustomerBonusTime
                $selevisionService = $this->getContainer()->get('selevisionService');
                $wsResponse = $selevisionService->callWSAction('giveCustomerBonusTime', $wsParam);
            
                $wsResponse['status'] = 1;
                if($wsResponse['status'] == 1){
                    
                    $compensationStatus = 'Success';
                    
                    //add compensation of expiry date code start
                    $expiryDate = $objUserActiveService->getExpiryDate()->format('Y-m-d H:i:s');
                
                    $currentExpiryDate = new \DateTime($expiryDate);
                    $newExpiryDate  = $currentExpiryDate->modify('+'.$totalIPTVExtendDays.' DAYS');
                
                    $objUserActiveService->setExpiryDate($newExpiryDate);
                    $objUserActiveService->setValidity($objUserActiveService->getValidity() + $totalIPTVExtendDays);
                    $em->persist($objUserActiveService);
                    $em->flush();
                    //end code of compensation
                    
                    $objCompensation->setIptvDays($totalIPTVExtendDays);
                                
                }else{
                    
                    if(isset($wsResponse['detail']) && !empty($wsResponse['detail'])){
    
                        $apiError = $wsResponse['detail'];
                    }
                }
            
                //Add Customer Compensation Log
                $this->storeCompensationLog($compensationStatus, $apiError, $objCompensation,$objCustomer,$objUserActiveService->getService());
                
                if($compensationStatus == 'Success'){
                    
                    $this->addInPurchaseHistory($objCompensation, $objCustomer,$objUserActiveService);
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function processCompensationOnISP($objCompensation,$objCustomer,$objUserActiveService,$autoExtend = false){
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        if($objCompensation && $objCustomer && $objUserActiveService){
            
            $totalISPExtendHours = 0;
            if($objCompensation->getIspHours()){
                
                $totalISPExtendHours = $objCompensation->getIspHours();
            }else{
            
                if($autoExtend){
            
                    $totalISPExtendHours = $this->convertDaysIntoHours($objCompensation->getIptvDays());
                }
            }
            
            $wsResponse = true;
            $compensationStatus = 'Failure';
            $apiError = '';
            
            if($wsResponse){
                
                //add compensation of expiry date code start
                $expiryDate = $objUserActiveService->getExpiryDate()->format('Y-m-d H:i:s');
                
                $currentExpiryDate = new \DateTime($expiryDate);
                $newExpiryDate  = $currentExpiryDate->modify('+'.$totalISPExtendHours.' DAYS');
                
                $objUserActiveService->setExpiryDate($newExpiryDate);
                $objUserActiveService->setValidity($objUserActiveService->getValidity() + $totalISPExtendHours);
                $em->persist($objUserActiveService);
                $em->flush();
                //end code of compensation
                
                $objCompensation->setIspHours($totalISPExtendHours);
                
                $compensationStatus = 'Success';
            }
            
            //Add Customer Compensation Log
            $this->storeCompensationLog($compensationStatus,$apiError, $objCompensation,$objCustomer,$objUserActiveService->getService());
            
            if($compensationStatus == 'Success'){
            
                $this->addInPurchaseHistory($objCompensation, $objCustomer,$objUserActiveService);
                
                return true;
            }
        }
        return false;
    }
    
    public function storeCompensationLog($compensationStatus, $apiError, $objCompensation, $objCustomer,$objService){
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        if($objCompensation && $objCustomer && $objService){
            
            $bonus = '';
            if(strtoupper($objService->getName()) == 'IPTV'){
                
                $bonus = $objCompensation->getIptvDays();
            }
            if(strtoupper($objService->getName()) == 'ISP'){
            
                $bonus = $objCompensation->getIspHours();
            } 
            
            $objCustomerCompensationLog = new CustomerCompensationLog();
            $objCustomerCompensationLog->setUser($objCustomer);
            $objCustomerCompensationLog->setBonus($bonus);
            $objCustomerCompensationLog->setServices($objService);
            $objCustomerCompensationLog->setStatus($compensationStatus);
            $objCustomerCompensationLog->setCompensation($objCompensation);
            $objCustomerCompensationLog->setApiError($apiError);
            $em->persist($objCustomerCompensationLog);
            $em->flush();
            
            return true;
        }
        
        return false;
    }
    
    public function addInPurchaseHistory($objCompensation, $objCustomer,$objUserActiveService){
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        $orderNumber = $this->generateOrderNumber();
        
        if($objCompensation && $objCustomer && $objUserActiveService){
            
            $compensationValidity = '';
            if($objUserActiveService->getService()){
                
                if(strtoupper($objUserActiveService->getService()->getName()) == 'IPTV'){
                
                    $compensationValidity = $objCompensation->getIptvDays();
                }
                if(strtoupper($objUserActiveService->getService()->getName()) == 'ISP'){
                
                    $compensationValidity = $objCompensation->getIspHours();
                }
            }
            $objPaymentMethod = $em->getRepository('LostServiceBundle:PaymentMethod')->findOneByCode('Compensation');
            
            //Save paypal response in PaypalExpressCheckOutCustomer table
            $objPurchaseOrder = new PurchaseOrder();
            $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
            $objPurchaseOrder->setSessionId('');
            $objPurchaseOrder->setOrderNumber($orderNumber);
            $objPurchaseOrder->setUser($objCustomer);
            $objPurchaseOrder->setTotalAmount(0);
            $objPurchaseOrder->setPaymentStatus('Completed');
            $objPurchaseOrder->setCompensationValidity($compensationValidity);
            
            $em->persist($objPurchaseOrder);
            $em->flush();
            $insertIdPurchaseOrder = $objPurchaseOrder->getId();
            
            if($insertIdPurchaseOrder){
                
                $objServicePurchase = new ServicePurchase();
                
                $objServicePurchase->setService($objUserActiveService->getService());
                $objServicePurchase->setUser($objCustomer);
                $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                $objServicePurchase->setPackageId($objUserActiveService->getPackageId());
                $objServicePurchase->setPackageName($objUserActiveService->getPackageName());
                $objServicePurchase->setActualAmount(0);
                $objServicePurchase->setPayableAmount(0);
                $objServicePurchase->setPaymentStatus('Completed');
                $objServicePurchase->setRechargeStatus(1);
                $objServicePurchase->setSessionId('');
                $objServicePurchase->setIsUpgrade(0);
                $objServicePurchase->setIsAddon(0);
                $objServicePurchase->setTermsUse(1);
                
                $em->persist($objServicePurchase);
                $em->flush();
                $insertIdServicePurchase = $objServicePurchase->getId();

                return true;
            }
        }
    
        return false;
    }
    
    public function sendCompensationMail($objCompensation,$objCustomer){
        
        if($objCompensation && $objCustomer){
            
            $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
            $compensationEmail = $this->getContainer()->getParameter('compensation_email');
            $toEmail   = $objCustomer->getEmail();

            $body = $this->getContainer()->get('templating')->renderResponse('LostUserBundle:Emails:compensation_email.html.twig', array('username' => $objCustomer, 'emailContent' => $objCompensation->getEmailContent()));
            
            $compensation_email = \Swift_Message::newInstance()
                                ->setSubject('Lost Telecom -'. $objCompensation->getEmailSubject())
                                ->setFrom($fromEmail)
                                ->setTo(array($toEmail,$compensationEmail))
                                ->setBody($body->getContent())
                                ->setContentType('text/html');
            
            if($this->getContainer()->get('mailer')->send($compensation_email)){
                
                return true;
            }
        }
        
        return false;
    }
    
    public function generateOrderNumber(){
    
        $today = date("Ymd");
        $rand = strtoupper(substr(uniqid(sha1(time())),0,4));
        return $today . $rand;
    }
    
    public function convertDaysIntoHours($days){
        
        $totalHours = 0;
        if($days){
            
            $totalHours = ceil($days * 24);
        }
        
        return $totalHours; 
    }
    
    public function convertHoursIntoDays($hours){
    
        $totalDays = 0;
        if($hours){
    
            $totalDays = ceil($hours / 24);
        }
    
        return $totalDays;
    }
}
