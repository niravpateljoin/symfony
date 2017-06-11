<?php

namespace Lost\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingControllerTest extends WebTestCase
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
    public function testAddSetting() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/setting/add-setting');

        $this->assertEquals('Lost\AdminBundle\Controller\SettingController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('add')->form();
        $form['lost_admin_setting[name]'] = static::$kernel->getContainer()->getParameter('test_setting_name');
        $form['lost_admin_setting[value]'] = static::$kernel->getContainer()->getParameter('test_setting_value');

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/admin/setting/setting-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();

        $this->assertEquals(1, $crawler->filter('html:contains("Setting added successfully!")')->count());
    }
    
    /** 
     * Test for editting Setting
     */
    public function testEditSetting() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $objSetting = $this->em->getRepository('LostAdminBundle:Setting')
                ->findOneByName(static::$kernel->getContainer()->getParameter('test_setting_name_update'));
        
        $crawler = $client->request('POST', '/admin/setting/edit-setting/'.$objSetting->getId());
        
        $form = $crawler->selectButton('update')->form();
        $form['lost_admin_setting[name]'] = static::$kernel->getContainer()->getParameter('test_setting_name_update');
        $form['lost_admin_setting[value]'] = static::$kernel->getContainer()->getParameter('test_setting_update_value');

        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/setting/setting-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Setting updated successfully!")')->count());
        
    }
    
    /**
     *  Testing for delete Setting
     */
    public function testDeleteSetting() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $objSetting = $this->em->getRepository('LostAdminBundle:Setting')
                ->findOneByName(static::$kernel->getContainer()->getParameter('test_setting_name'));
        
        $crawler = $client->request('POST', '/admin/setting/delete-setting/'.$objSetting->getId());
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/setting/setting-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Setting deleted successfully!")')->count());
        
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
