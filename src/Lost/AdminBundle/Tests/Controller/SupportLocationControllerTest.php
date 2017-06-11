<?php

namespace Lost\UserBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SupportLocationControllerTest extends WebTestCase {

    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    /**
     *  General function for Admin login
     */
    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', '/admin/login');

        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();
    }
    
    /**
     * This action allows to test add support category
     */
    public function testAddSupportLocation() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/support-location/add-location');

        $this->assertEquals('Lost\AdminBundle\Controller\SupportLocationController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('add')->form();
        
        $form['lost_user_support_location[name]'] = "NewOne";
        $form['lost_user_support_location[code]'] = "NN";
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-location/location-list')); // check if redirecting properly

        $crawler = $client->followRedirect();

        $this->assertEquals(1, $crawler->filter('html:contains("Support location added successfully.")')->count());
    }

    /**
     * This action allows to test edit support category
     */
    public function testEditSupportCategory() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $objSetting = $this->em->getRepository('LostUserBundle:SupportLocation')
                ->findOneBy(array('name' => 'NewOne'));
        
        $crawler = $client->request('POST', '/admin/support-location/edit-location/' . $objSetting->getId());

        $this->assertEquals('Lost\AdminBundle\Controller\SupportLocationController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('update')->form();
        $form['lost_user_support_location[name]'] = "UpdatedNew";
        $form['lost_user_support_location[code]'] = "UN";
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-location/location-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Support location updated successfully.")')->count());
    }
    
    /**
     * This action allows to test edit support category
     */
    public function testDeleteSupportCategory() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $objSetting = $this->em->getRepository('LostUserBundle:SupportLocation')
                ->findOneBy(array('name' => 'UpdatedNew'));
        
        $crawler = $client->request('POST', '/admin/support-location/delete-location/' . $objSetting->getId());

        $this->assertEquals('Lost\AdminBundle\Controller\SupportLocationController::deleteAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-location/location-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Support location deleted successfully.")')->count());
    }
    
    /**
     *  Testing for Admin login
     */
    public function testLogin() {
        
         $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $username = static::$kernel->getContainer()->getParameter('test_admin_username');
        $password = static::$kernel->getContainer()->getParameter('test_admin_password');
        
        $crawler = $client->request('GET', '/admin/login');

        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/admin/dashboard')); // check if redirecting properly

        $client->followRedirect();
    }

}
