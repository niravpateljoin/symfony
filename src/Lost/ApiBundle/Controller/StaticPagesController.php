<?php

namespace Lost\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StaticPagesController extends Controller {

    public function getPageContentAction(Request $request) {

        $em = $this->getDoctrine()->getManager();
		$body = $request->getContent();
        $data = json_decode($body, true);
		
		$strPageName = $data['name'];

        $objStaticPages = $em->getRepository('LostFrontBundle:Pages')->findOneBy(array('name' => $strPageName, 'status' => 'Active'));
		
		$arrPage = array();
		$arrPage['name'] = $objStaticPages->getName();
		$arrPage['content'] = $objStaticPages->getContent();

        $response = new Response(json_encode($arrPage));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
