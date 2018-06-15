<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Token;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package Tests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected $c;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var BCryptPasswordEncoder
     */
    protected $encoder;

    /**
     * DatabaseTestCase constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = NULL, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        self::bootKernel();
        $this->c       = self::$kernel->getContainer();
        $this->dm      = $this->c->get('doctrine_mongodb.odm.default_document_manager');
        $this->encoder = new BCryptPasswordEncoder(12);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     */
    protected function loginUser(string $username, string $password): User
    {
        $this->session      = $this->c->get('session');
        $this->tokenStorage = $this->client->getContainer()->get('security.token_storage');
        $this->session->invalidate();
        $this->session->start();

        $user = new User();
        $user
            ->setEmail($username)
            ->setPassword($this->encoder->encodePassword($password, ''));

        $this->persistAndFlush($user);

        $token = new Token($user, $password, SecurityManager::SECURED_AREA, ['test']);
        $this->tokenStorage->setToken($token);

        $this->session->set(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA, serialize($token));
        $this->session->save();

        $cookie = new Cookie($this->session->getName(), $this->session->getId());
        $this->client->getCookieJar()->set($cookie);

        return $user;
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient([], []);
        $this->dm->getConnection()->dropDatabase('pipes');
        $this->loginUser('test@example.com', 'password');
    }

    /**
     * @param mixed $document
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush($document);
    }

    /**
     * @param string $url
     *
     * @return stdClass
     */
    protected function sendGet(string $url): stdClass
    {
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => json_decode($response->getContent()),
        ];
    }

    /**
     * @param string     $url
     * @param array      $parameters
     * @param array|null $content
     *
     * @return stdClass
     */
    protected function sendPost(string $url, array $parameters, ?array $content = NULL): stdClass
    {
        $this->client->request('POST', $url, $parameters, [], [], $content ? json_encode($content) : '');
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => json_decode($response->getContent()),
        ];
    }

    /**
     * @param string     $url
     * @param array      $parameters
     * @param array|null $content
     *
     * @return stdClass
     */
    protected function sendPut(string $url, array $parameters, ?array $content = NULL): stdClass
    {
        $this->client->request('PUT', $url, $parameters, [], [], $content ? json_encode($content) : '');
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => json_decode($response->getContent()),
        ];
    }

    /**
     * @param string $url
     *
     * @return stdClass
     */
    protected function sendDelete(string $url): stdClass
    {
        $this->client->request('DELETE', $url);
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => json_decode($response->getContent()),
        ];
    }

}