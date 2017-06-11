<?php

namespace Lost\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * 
 */
class EmailCampaignControllerTest extends WebTestCase {

    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    /**
     * This action allows to test the updating account page for a user
     */
    public function testAddEmailCampaign() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('POST', '/admin/email-campaign/add-email-campaign');
        $client->enableProfiler();


        $subject = static::$kernel->getContainer()->getParameter('test_email_campaign_subject');
        $message = static::$kernel->getContainer()->getParameter('test_email_campaign_message');
        $startDate = static::$kernel->getContainer()->getParameter('test_email_campaign_start_date');
        $endDate = static::$kernel->getContainer()->getParameter('test_email_campaign_end_date');
        $emailType = static::$kernel->getContainer()->getParameter('test_email_campaign_email_type');
        
        $this->assertEquals('Lost\AdminBundle\Controller\EmailCampaignController::newAction', $client->getRequest()->attributes->get('_controller'));

//        $user = $this->em->getRepository('LostUserBundle:User')->getUserByUsernameOrEmail($username, $email);
//        if ($user) {
//            $this->assertEquals($user['email'], $email);
//        }
        $form = $crawler->selectButton('Add')->form();
        $form['lost_admin_email_campaign[subject]'] = $subject;
        $form['lost_admin_email_campaign[message]'] = $message;
        $form['lost_admin_email_campaign[startDate]'] = $startDate;
        $form['lost_admin_email_campaign[endDate]'] = $endDate;
        $form['lost_admin_email_campaign[emailType]'] = $emailType;
        
        $crawler = $client->submit($form);
    }

    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', 'admin/login');
        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }

}
