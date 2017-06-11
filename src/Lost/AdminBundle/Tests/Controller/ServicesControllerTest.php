<?php

namespace Lost\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServicesControllerTest extends WebTestCase
{
    
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    /**
     * Testing for Adding new Setting
     */
    public function testAddServices() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/service/add-service');

        $this->assertEquals('Lost\AdminBundle\Controller\ServicesController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('add')->form();
        $form['lost_service_add[name]'] = static::$kernel->getContainer()->getParameter('test_service_name');
        $form['lost_service_add[status]'] = static::$kernel->getContainer()->getParameter('test_service_status');

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/admin/service/service-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();

        $this->assertEquals(1, $crawler->filter('html:contains("Service added successfully!")')->count());
    }
    
    /** 
     * Test for editting Setting
     */
    public function testEditServices() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
//        $objServices = $this->em->getRepository('LostUserBundle:Services')
//                ->findOneByName(static::$kernel->getContainer()->getParameter('test_service_name'));
        
        //$crawler = $client->request('POST', '/admin/service/edit-service/'.$objServices->getId());
        $crawler = $client->request('POST', '/admin/service/edit-service/'.static::$kernel->getContainer()->getParameter('test_service_by_id'));
        
        $form = $crawler->selectButton('update')->form();
        $form['lost_service_add[name]'] = static::$kernel->getContainer()->getParameter('test_service_name_update');
        $form['lost_service_add[status]'] = static::$kernel->getContainer()->getParameter('test_service_status_update');

        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/service/service-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Service updated successfully!")')->count());
        
    }
    
    /**
     *  Testing for delete Setting
     */
    public function testDeleteServices() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/service/delete-service/'.static::$kernel->getContainer()->getParameter('test_service_by_id'));
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/service/service-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Service deleted successfully!")')->count());
        
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
}
