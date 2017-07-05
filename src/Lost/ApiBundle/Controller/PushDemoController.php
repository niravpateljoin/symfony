<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use RMS\PushNotificationsBundle\Message\iOSMessage;
use RMS\PushNotificationsBundle\Message\AndroidMessage;

class PushDemoController extends Controller {

    public function pushAction(Request $request) {
        
        $message = new iOSMessage();
        $message->setMessage('Oh my! A push notification!');
        $message->setDeviceIdentifier('test012fasdf482asdfd63f6d7bc6d4293aedd5fb448fe505eb4asdfef8595a7');

        $this->container->get('rms_push_notifications')->send($message);

        return new Response('Push notification send!');
    }
    
    public function pushAndroidAction(Request $request) {
        
        $data['message']= 'Oh my! A push notification!';
        $data['redirect']= '/refer-list';
        
        
        $message = new AndroidMessage();
        $message->setGCM(true);
        $message->setMessage(json_encode($data));
        $message->setDeviceIdentifier('fhT6isyco84:APA91bFr2z47BnF4RIB8BXjwh87kgmPlFsC5D2OAlcpuohHrgCe0jvq3IlniGyvJrDG0hGXBUUOr2cDWgo7YoHTZ073-r096-JHGoooHAAjDfFPAZ5keAR1W8GmcfSxlSTUhEb4q8Zun');

        $this->container->get('rms_push_notifications')->send($message);
        
        //return new Response('Push notification send!');
        return new Response(json_encode($data));
    }

}
