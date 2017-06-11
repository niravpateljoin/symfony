<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lost\UserBundle\Controller;

#use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\UserBundle\Controller\SecurityController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SecurityController extends Controller
{
    /**
     * Renders the login template with the given parameters. Overwrite this function in
     * an extended controller to provide additional data for the login template.
     *
     * @param array $data
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderLogin(array $data)
    { 
        $user = $this->get('security.context')->getToken()->getUser();
        if(is_object($user)) {
            $request = $this->get('request');
            if(strpos($request->getPathInfo(), '/admin/') !== false) { 
                $url = $this->generateUrl('lost_admin_dashboard');
            } else {
                $url = $this->generateUrl('lost_user_account');
            }
            return new RedirectResponse($url);
        } 
        return $this->render('LostUserBundle:Security:login.html.twig', $data);
    }
}
