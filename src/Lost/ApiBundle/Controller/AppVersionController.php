<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppVersionController extends Controller {

    public function versionAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $message = "";
        $status = "success";

        $final_response_data = array(
            'androidVersion' => '1.0',
            'iOSVersion' => '1.0',
            'apiVersion' => '1.0',
            'message' => '',
            'status' => 'success'
        );

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
    
    public function version11Action(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $message = "";
        $status = "success";

        $final_response_data = array(
            'AndroidVersion' => '1.0',
            'iOSVersion' => '1.0',
            'message' => '',
            'status' => 'success'
        );

        $response = new Response(json_encode($final_response_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
