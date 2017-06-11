<?php

namespace Lost\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * 
 */
class AdminControllerTest extends WebTestCase {

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
    public function testAccountData() {
        $client = static::createClient(
                        array(), array(
                    //'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('POST', '/admin/add-new-admin');
        $client->enableProfiler();


        $email = static::$kernel->getContainer()->getParameter('test_admin_email');
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $password = static::$kernel->getContainer()->getParameter('test_password');
        $repeatPassword = static::$kernel->getContainer()->getParameter('test_repeat_password');
        $role = static::$kernel->getContainer()->getParameter('test_role');
        $active = static::$kernel->getContainer()->getParameter('test_active');

        $this->assertFalse(strlen($username) < 8, 'The username minimum length should be 8');
        $this->assertFalse(strlen($username) > 18, 'The username maximum length should be 18');
        $this->assertFalse(strlen($password) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($password) > 18, 'The password maximum length should be 18');

        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $email);
        $this->assertRegExp('/^[A-Za-z0-9-_!#$]+$/', $username);
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $password);

        $this->assertEquals($password, $repeatPassword);
        $this->assertEquals('Lost\AdminBundle\Controller\AdminController::newAction', $client->getRequest()->attributes->get('_controller'));

        $user = $this->em->getRepository('LostUserBundle:User')->getUserByUsernameOrEmail($username, $email);
        if ($user) {
            $this->assertEquals($user['email'], $email);
        }
        $form = $crawler->selectButton('Add')->form();
        $form['lost_admin_registration[email]'] = $email;
        $form['lost_admin_registration[username]'] = $username;
        $form['lost_admin_registration[plainPassword][first]'] = $password;
        $form['lost_admin_registration[plainPassword][second]'] = $repeatPassword;
        $form['lost_admin_registration[access]'] = $role;
        $form['lost_admin_registration[enabled]'] = $active;

        $crawler = $client->submit($form);
    }

    private function getMockPasswordEncoder() {
        return $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
    }

    private function getUser() {
        return $this->getMockBuilder('FOS\UserBundle\Model\User')
                        ->getMockForAbstractClass();
    }

    private function getMockCanonicalizer() {
        return $this->getMock('FOS\UserBundle\Util\CanonicalizerInterface');
    }

    private function getMockEncoderFactory() {
        return $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
    }

    private function getUserManager(array $args) {
        return $this->getMockBuilder('FOS\UserBundle\Model\UserManager')
                        ->setConstructorArgs($args)
                        ->getMockForAbstractClass();
    }

    public function doLogin($username, $password, $client) {
        $crawler = $client->request('POST', 'admin/login');
        //echo $client->getResponse()->getContent();exit;
        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }

}
