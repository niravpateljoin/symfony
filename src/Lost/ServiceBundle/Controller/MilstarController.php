<?php

namespace Lost\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\Definition\Exception\Exception;


class MilstarController extends Controller
{
    protected $container;
    protected $em;
    protected $session;
    protected $securitycontext;
    
    protected $milstar_region;
    protected $milstar_wsdl;
    
    public function __construct($container) {
        
        $this->container = $container;
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->milstar_region    = $this->container->getParameter('milstar_region');
        $this->milstar_wsdl      = $this->container->getParameter('milstar_wsdl');
        $this->PaymentProcess = $container->get('PaymentProcess');
    }
    
    
    // Milstar for approval
    public function processMilstarApproval($milsatParams) {
        
        $trancationStatus = false;
        $milsatParams['region'] = $this->milstar_region;
        
        $requestId        = $milsatParams['requestId'];
        $creditCardNumber = $milsatParams['creditCardNumber'];
        $amount           = $milsatParams['amount'];
        $processingFacnbr = $milsatParams['processingFacnbr'];
        $zipCode          = $milsatParams['zipCode'];
        $cid              = $milsatParams['cid'];
        
        
        // Construct the XML string that will be sent to MilStar	
 $msApproval=<<<XML
        <MSApproval xmlns='MSApproval'>
            <Request ID="$requestId">
                <CCNumber>$creditCardNumber</CCNumber>
                <Amount>$amount</Amount>
                <FacNbr>$processingFacnbr</FacNbr>
                <Region>$this->milstar_region</Region>
                <ZipCode>$zipCode</ZipCode>
                <CID>$cid</CID>
            </Request>
        </MSApproval>
XML;
        
        try {
            //A SoapFault exception will be thrown if the wsdl URI cannot be loaded.
    	    $client = new \SoapClient($this->milstar_wsdl, array('trace' => 1,'exceptions' => 1));				
    
    		$responseXML = $client->MSApproval(array(
    					"inXMLApproval" => html_entity_decode($msApproval)
    			));	
    		}
        catch (SoapFault $e) {
    	    
    	    $milsatParams['failCode']	 = "WC";
    		$milsatParams['failMessage'] = $e->getMessage();    		
    		
    		$this->PaymentProcess->storeMilstarResponse($milsatParams);
        }
    
    
        $responseObject = simplexml_load_string($responseXML->MSApprovalResult);
        
        /*
         *  ReturnCode values
        *  "A" - Approved,  ReturnMessage will be NULL
        *  "D" - Denied, ReturnMessage will have applicable error message
        *  "X" - Error,  ReturnMessage will have applicable error message
        *  "E" - Validation Error, ReturnMessage will have applicable error message
        */
        $milsatParams['processStatus'] = 'MSApproval';
        switch($responseObject->Response->ReturnCode)	{
            case "A":
                if($this->processMilstarSettle($responseObject,$milsatParams)){
                    
                    $trancationStatus['authCode']   = $responseObject->Response->AuthCode;
                    $trancationStatus['authTicket'] = $responseObject->Response->AuthTkt;
                }
                break;
            case "D":
                $this->processMilstarResponse($responseObject,$milsatParams);
                break;
            case "X":
                $this->processMilstarResponse($responseObject,$milsatParams);
                break;
            case "E":
                $this->processMilstarResponse($responseObject,$milsatParams);
                break;
            default:
                throw new Exception("Error Invalid ReturnCode");
        }      

        return $trancationStatus;
    }
    
    // milstar for settle
    public function processMilstarSettle($responseObject,$milsatParams) {
        
        $trancation = false;
        
        $requestID 			= $responseObject->children()->attributes()->ID;
		$creditCardNumber   = $responseObject->Request->CCNumber;
		$amount 			= $responseObject->Request->Amount;
		$processingFacnbr 	= $responseObject->Request->FacNbr;
		$region 			= $responseObject->Request->Region;
		$auth_code 			= $responseObject->Response->AuthCode;
		$auth_tkt 			= $responseObject->Response->AuthTkt;
		
		$msSettle = <<<XML
                <MSSettle xmlns="MSSettle">
                    <Request ID="$requestID">
                        <CCNumber>$creditCardNumber</CCNumber>
                        <Amount>$amount</Amount>
                        <FacNbr>$processingFacnbr</FacNbr>
                        <AuthCode>$auth_code</AuthCode>
                        <AuthTkt>$auth_tkt</AuthTkt>
                        <Region>$this->milstar_region</Region>
                    </Request>
                </MSSettle>
XML;
        
        try {
            $client = new \SoapClient($this->milstar_wsdl, array('trace' => 1,'exceptions' => 1));

            $responseXML = $client->MSSettlement(array(
                "inXMLSettle" => html_entity_decode($msSettle)
            ));
        } catch (SoapFault $e) {
            
            $milsatParams['requestId']         = $requestID;
            $milsatParams['amount']            = $amount;
            $milsatParams['processingFacnbr']  = $processingFacnbr;
            $milsatParams['region']            = $region;            
            $milsatParams['failCode']	       = "WC";
            $milsatParams['failMessage']       = $e->getMessage();
            $milsatParams['region']            = $this->milstar_region;
            
            $this->PaymentProcess->storeMilstarFailureResponse($milsatParams);
        }
        
        $responseObject = simplexml_load_string($responseXML->MSSettlementResult);
        
        
        /*
		*  ReturnCode values
		*  "A" - Approved,  ReturnMessage will be NULL
		*  "D" - Denied, 	ReturnMessage will have applicable error message
		*  "X" - Error, 	ReturnMessage will have applicable error message
		*  "E" - Validation Error, ReturnMessage will have applicable error message 
		*/	
        
        $milsatParams['processStatus'] = 'MSSettlement';
		switch($responseObject->Response->ReturnCode)	{
			case "A":			
			    $milsatParams['failCode']    = NULL;
			    $milsatParams['failMessage'] = NULL;
			    
			    $this->processMilstarResponse($responseObject,$milsatParams);
			    
			    $trancation = true;
				break;
			case "D":
				$this->processMilstarResponse($responseObject,$milsatParams);
				break;
			case "X":
				$this->processMilstarResponse($responseObject,$milsatParams);
				break;
			case "E":
				$this->processMilstarResponse($responseObject,$milsatParams);
				break;
			default:
				$this->processMilstarResponse($responseObject,$milsatParams);
		}
		
		return $trancation;        
    }
    // milstar for credit
    public function processMilstarCredit($milsatParams) {
        
        $trancation = false;
        
        $requestId         = $milsatParams['requestId'];
        $creditCardNumber  = $milsatParams['creditCardNumber'];
        $amount            = $milsatParams['amount'];
        $processingFacnbr  = $milsatParams['processingFacnbr'];
        $authCode          = $milsatParams['authCode'];
        $authTkt           = $milsatParams['authTicket'];
        
        $milsatParams['processStatus'] = 'MSCredit';
        $milsatParams['region']        = $this->milstar_region;
        
        
        $msCreditXml=<<<XML
                    <MSCredit xmlns="MSCredit">
                        <Request ID="$requestId">
                            <CCNumber>$creditCardNumber</CCNumber>
                            <Amount>$amount</Amount>
                            <FacNbr>$processingFacnbr</FacNbr>
                            <AuthCode>$authCode</AuthCode>
                            <AuthTkt>$authTkt</AuthTkt>
                            <Region>$this->milstar_region</Region>
                        </Request>
                    </MSCredit>
XML;
            
        try {
            $client = new \SoapClient($this->milstar_wsdl, array('trace' => 1,'exceptions' => 1));
        
            $responseXML = $client->MSCredit(array(
                    "inXMLCredit" => html_entity_decode($msCreditXml)
            ));
        } catch (SoapFault $e) {
            
            $milsatParams['requestId']         = $requestID;
            $milsatParams['amount']            = $amount;
            $milsatParams['processingFacnbr']  = $processingFacnbr;
            $milsatParams['region']            = $region;
            $milsatParams['failCode']	       = "WC";
            $milsatParams['failMessage']       = $e->getMessage();
           
            
            $this->PaymentProcess->processMilstarResponse($milsatParams);
        }
        
        
        $responseObject = simplexml_load_string($responseXML->MSCreditResult);
        
        /*
		*  ReturnCode values
		*  "A" - Approved,  ReturnMessage will be NULL
		*  "D" - Denied, 	ReturnMessage will have applicable error message
		*  "X" - Error, 	ReturnMessage will have applicable error message
		*  "E" - Validation Error, ReturnMessage will have applicable error message 
		*/	
		switch($responseObject->Response->ReturnCode)	{
		    
			case "A":			    
			    $milsatParams['failCode']    = NULL;
			    $milsatParams['failMessage'] = NULL;
			    $this->processMilstarResponse($responseObject,$milsatParams);
				$trancation = true;
				break;
			case "D":
				$this->processMilstarResponse($responseObject,$milsatParams);
				break;
			case "X":
				$this->processMilstarResponse($responseObject,$milsatParams);
				break;
			case "E":
				$this->processMilstarResponse($responseObject,$milsatParams);
				break;
			default:
				$this->processMilstarResponse($responseObject,$milsatParams);
		}
		
		return $trancation;
    }  
    
    function processMilstarResponse($responseObject,$milsatParams)
    {        
        //data is now in the xml request part of the response
        $returnCode 	 = $responseObject->Response->ReturnCode;
        $returnMessage   = $responseObject->Response->ReturnMessage;
        
        
        $milsatParams['failCode']     = $returnCode;
        $milsatParams['failMessage']  = $returnMessage;
        $milsatParams['responseCode'] = $returnCode;
        $milsatParams['message'] = '';
        
        switch($returnCode)	{
            case "D":
                $milsatParams['message'] = 'Declined, Please try another payment method or contact support with the following info: ';
                break;
            case "X":
                $milsatParams['message'] = 'Error, Please try another payment method or contact support with the following info: ';
                break;
            case "E":
                $milsatParams['message'] = 'Error, Please try another payment method or contact support with the following info: ';
                break;            
        }
        
        $this->PaymentProcess->storeMilstarResponse($milsatParams);
    }    
}
