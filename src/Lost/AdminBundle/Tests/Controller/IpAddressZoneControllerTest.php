<?php

namespace Lost\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IpAddressZoneControllerTest extends WebTestCase
{
    
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    /**
     * Testing for Adding new IP address range
     */
    public function testAddIpAddressZone() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/add-ipzone');

        $this->assertEquals('Lost\AdminBundle\Controller\IpAddressZoneController::newAction', $client->getRequest()->attributes->get('_controller'));

        $from_ip = static::$kernel->getContainer()->getParameter('test_from_ip');
        $to_ip = static::$kernel->getContainer()->getParameter('test_to_ip');
        
        $form = $crawler->selectButton('add')->form();
        $form['ip_address_zone_add[fromIpAddress]'] = $from_ip;
        $form['ip_address_zone_add[toIpAddress]'] = $to_ip;
        $form['ip_address_zone_add[service]'] = static::$kernel->getContainer()->getParameter('test_service_id');
        
        $this->assertFalse(!filter_var($from_ip, FILTER_VALIDATE_IP));
        $this->assertFalse(!filter_var($to_ip, FILTER_VALIDATE_IP));
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/ipzone-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();

        $this->assertEquals(1, $crawler->filter('html:contains("IP address range added successfully!")')->count());
    }
    
    /** 
     * Test for editting IP address range
     */
    public function testEditIpAddressZone() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/edit-ipzone/'.static::$kernel->getContainer()->getParameter('test_ipzone_by_id'));
        
        $from_ip = static::$kernel->getContainer()->getParameter('test_from_ip');
        $to_ip = static::$kernel->getContainer()->getParameter('test_to_ip');
        
        $form = $crawler->selectButton('edit')->form();
        $form['ip_address_zone_add[fromIpAddress]'] = $from_ip;
        $form['ip_address_zone_add[toIpAddress]'] = $to_ip;
        $form['ip_address_zone_add[service]'] = static::$kernel->getContainer()->getParameter('test_service_id');
        
        $this->assertFalse(!filter_var($from_ip, FILTER_VALIDATE_IP));
        $this->assertFalse(!filter_var($to_ip, FILTER_VALIDATE_IP));
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/ipzone-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("IP address range updated successfully!")')->count());
        
    }
    
    /**
     *  Testing for delete IP address range
     */
    public function testDeleteIpAddressZone() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/delete-ipzone/'.static::$kernel->getContainer()->getParameter('test_delete_ipzone_by_id'));
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/ipzone-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("IP address range deleted successfully!")')->count());
        
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
