<?php

namespace Dispensaries\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionListener {

    protected $container;

    public function __construct(ContainerInterface $container) { // this is @service_container
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelController(FilterControllerEvent $event) {
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'))->get('application/json');

        //  echo "<pre>"; print_r($acceptHeader); exit;
        if (!is_null($acceptHeader)) {
            
            $version = $acceptHeader->getAttribute('version');
            $route = $event->getRequest()->get('_route');

            $request->attributes->set('version', '1.0');
            $request->attributes->set('current_route_path', $route);

            /*if (substr($route, 0, 4) == 'api_') {

                $body = $request->getContent();
                $data = json_decode($body, true);

                if (!empty($data['userId'])) {
                    $objCommonHelper = $this->container->get('dispensaries.helper.common_api');
                    $securityTokenValid = $objCommonHelper->checkSecureAPI($data['deviceToken'], $data['userId']);

                    if (!empty($securityTokenValid)) {

                        $response = new Response(json_encode($securityTokenValid));
                        $response->headers->set('Content-Type', 'application/json');

                        return $response;
                        // return new Response(json_encode($securityTokenValid));
                    }
                } else {
                    $result['status'] = 'error';
                    $result['message'] = 'Invalid UserId.';

                    return new Response(json_encode($result));
                }
            }*/
        }
    }

}
