<?php

namespace Lost\UserBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SupportCategoryControllerTest extends WebTestCase {

    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    /**
     * This action allows to test add support category
     */
    public function testAddSupportCategory() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/support-category/add-category');

        $this->assertEquals('Lost\AdminBundle\Controller\SupportCategoryController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('add')->form();
        
        $form['lost_user_support_category[name]'] = "UnitTesting";
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-category/category-list')); // check if redirecting properly

        $crawler = $client->followRedirect();

        $this->assertEquals(1, $crawler->filter('html:contains("You have successfully added support category.")')->count());
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

        $objSetting = $this->em->getRepository('LostUserBundle:SupportCategory')
                ->findOneBy(array('name' => 'UnitTesting'));
        
        $crawler = $client->request('POST', '/admin/support-category/edit-category/' . $objSetting->getId());

        $this->assertEquals('Lost\AdminBundle\Controller\SupportCategoryController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('update')->form();
        $form['lost_user_support_category[name]'] = "UnitTesting1";
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-category/category-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("You have successfully updated support category.")')->count());
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

        $objSetting = $this->em->getRepository('LostUserBundle:SupportCategory')
                ->findOneBy(array('name' => 'UnitTesting1'));
        
        $crawler = $client->request('POST', '/admin/support-category/delete-category/' . $objSetting->getId());

        $this->assertEquals('Lost\AdminBundle\Controller\SupportCategoryController::deleteAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-category/category-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Support category deleted successfully!")')->count());
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
