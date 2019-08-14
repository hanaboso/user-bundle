<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Token;
use stdClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package Tests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    /**
     * @var KernelBrowser
     */
    protected $client;

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
     * @var NativePasswordEncoder
     */
    protected $encoder;

    /**
     * ControllerTestCaseAbstract constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = NULL, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        self::bootKernel();
        $this->dm      = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->encoder = new NativePasswordEncoder(12);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     * @throws Exception
     */
    protected function loginUser(string $username, string $password): User
    {
        $this->session      = self::$container->get('session');
        $this->tokenStorage = self::$container->get('security.token_storage');
        $this->session->invalidate();
        $this->session->start();

        $user = new User();
        $user
            ->setEmail($username)
            ->setPassword($this->encoder->encodePassword($password, ''));

        $this->persistAndFlush($user);

        $token = new Token($user, $password, SecurityManager::SECURED_AREA, ['test']);
        $this->tokenStorage->setToken($token);

        $this->session->set(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA),
            serialize($token)
        );
        $this->session->save();

        $cookie = new Cookie($this->session->getName(), $this->session->getId());
        $this->client->getCookieJar()->set($cookie);

        return $user;
    }

    /**
     * @throws Exception
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

        return $this->returnResponse($this->client->getResponse());
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
        $this->client->request('POST', $url, $parameters, [], [], $content ? (string) json_encode($content) : '');

        return $this->returnResponse($this->client->getResponse());
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
        $this->client->request('PUT', $url, $parameters, [], [], $content ? (string) json_encode($content) : '');

        return $this->returnResponse($this->client->getResponse());
    }

    /**
     * @param string $url
     *
     * @return stdClass
     */
    protected function sendDelete(string $url): stdClass
    {
        $this->client->request('DELETE', $url);

        return $this->returnResponse($this->client->getResponse());
    }

    /**
     * @param Response $response
     *
     * @return stdClass
     */
    protected function returnResponse(Response $response): stdClass
    {
        $content = json_decode($response->getContent(), TRUE);
        if (isset($content['error_code'])) {
            $content['errorCode'] = $content['error_code'];
            unset($content['error_code']);
        }

        $std          = new stdClass();
        $std->status  = $response->getStatusCode();
        $std->content = (object) $content;

        return $std;
    }

}
