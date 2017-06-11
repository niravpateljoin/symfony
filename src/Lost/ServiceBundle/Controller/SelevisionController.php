<?php

namespace Lost\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SelevisionController extends Controller
{
    protected $container;
    protected $em;


    public function __construct($container) {
    
        $this->container = $container;
        $this->securitycontext   = $container->get('security.context');
        $this->em                = $container->get('doctrine')->getManager();
        
    }
    
    public function callWSAction($action,$param = array())
    {
        $response = array();

        if($action){

            switch ($action) {

                //Create a new operator
                case 'createOperator':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Update an existing operator
                case 'updateOperator':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Update operator password
                case 'updateOperatorPwd':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Give credit to customer's wallet
                case 'giveOperatorCredit':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get customer history purchase
                case 'getCustomerPurchases':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Create a customer
                case 'createCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Change a customer's password
                case 'changeCustomerPwd':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Disable a customer
                case 'deactivateCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Reactivate a customer (after doing Disable a customer)
                case 'reactivateCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get customer password
                case 'getCustomerPwd':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Update a customer
                case 'updateCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Give channel package to customer
                case 'setCustomerOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Remove channel package from a customer
                case 'unsetCustomerOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get channel packages from a customer
                case 'getCustomerOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get all channel packages
                case 'getAllOffers':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                    
                case 'getChannelsFromOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'registerMac':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'giveCustomerBonusTime':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'customerMac':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                default:
                    $response['status'] = 0;
                    $response['detail'] = 'Selevision action mismatch';
            }

        }else{

            $response['status'] = 0;
            $response['detail'] = 'Unable to find valid parameter.';
        }
        
        return $response;
    }

    public function sendWsRequest($action,$postParam){

        $response = array();

        $API_URL                 = $this->container->getParameter('selevision_api_url');
        $Selevision_adm_username = $this->container->getParameter('selevision_admin_username');
        $Selevision_adm_pass     = $this->container->getParameter('selevision_admin_pass');

        if($API_URL && $Selevision_adm_username && $Selevision_adm_pass){

            $postParam['adLogin'] = $Selevision_adm_username;
            $postParam['adPwd']   = $Selevision_adm_pass;
            
            $postStr = http_build_query($postParam);
            
            // Set the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $API_URL.$action.'.php?'.$postStr);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);

            // Turn off the server and peer verification (TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // Get response from the server.
            $httpResponse = curl_exec($ch);
            
            $info = curl_getinfo($ch);
            
            
            if($info['http_code'] != '404'){
                    
                if(!$httpResponse) {
                    
                    $response['status'] = '0';
                    $response['msg']    = '('.curl_errno($ch).') '.curl_error($ch);                
                }else{
                    
                    $httpResponse = json_decode($httpResponse, true);
                    
                    if(count($httpResponse) > 0){
                        
                        if(isset($httpResponse['status']) && !empty($httpResponse['status'])){
                            
                            $httpResponse['status'] = ($httpResponse['status'] == 'fail')?0:1;
                        }
                        
                        if($action == 'getAllOffers' || $action == 'getChannelsFromOffer'){
                            
                            $response['data'] = $httpResponse;
                            $response['status'] = 1;
                        }else{
                            $response = $httpResponse;
                        }                
                    }else{
                        
                        $response['status'] = 1;
                        $response['detail'] = 'Data not found';
                    }
                    
                }            
            }else{
                
                $response['status'] = 0;
                $response['detail']    = '404: Requested webservice url not found.';
            }
        }else{
            
            $response['status'] = 0;
            $response['detail']    = 'Unable to find api credential.';
        }

        return $response;
    }
    
    public function getAllPackageDetails() {
        
        $packages = $this->callWSAction('getAllOffers', array());       
        $packageArr = array();

        if(!empty($packages['data'])){
            
            foreach($packages['data'] as $package) {
    
                $packageArr[$package['offerName']]['packageId'] = $package['offerId'];
                $packageArr[$package['offerName']]['packageName'] = $package['offerName'];
                $packageArr[$package['offerName']]['packagePrice'] = $package['offerPrice'] + 10;
            }
    //        echo "<pre>"; print_r($packageArr);exit;
    
            $wsParam['channels'] = '';
            $packagesWithChannels = $this->callWSAction('getAllOffers', $wsParam);
            
            foreach($packagesWithChannels['data'] as $package) {
                $packageArr[$package['offerName']]['packageChannelCount'] = count($package['offerChannels']);
    //             $packageArr[$package['offerName']]['packageChannels'] = $package['offerChannels'];
            }
        }
        return $packageArr;
    }
    
    public function checkUserExistInSelevision() {
        
        $user = $this->securitycontext->getToken()->getUser();
        
        // check selevision api to check whether customer exist in system
        $wsParam = array();
        $wsParam['cuLogin'] = $user->getUsername();
        
        $selevisionService = $this->get('selevisionService');
        $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);
        
        return $wsResponse['status'];
    }
    
    public function createNewUser() {
        
        $user = $this->securitycontext->getToken()->getUser();
        $flag = false;
        
        if (!$this->checkUserExistInSelevision()) {
        
            $wsAddCustParam = array();
            $wsAddCustParam['cuFirstName']  = $user->getFirstName();
            $wsAddCustParam['cuLastName']   = $user->getLastName();
            $wsAddCustParam['cuEmail']      = $user->getEmail();
            $wsAddCustParam['cuLogin']      = $user->getUserName();
            $wsAddCustParam['cuPwd']        = $user->getUserName();
        
            $selevisionService = $this->get('selevisionService');
            $wsRes = $selevisionService->callWSAction('createCustomer',$wsAddCustParam);
            
            if($wsRes['status'] == 1){

                //Update New user flag
                $user->setNewSelevisionUser(1);
                $this->em->persist($user);
                $this->em->flush();
                
                $flag = true;
            }else{
                
                $flag = false;
            }
        }else{
            
            $flag = true;
        }
        //$flag = true;
        return $flag;        
    }
}
