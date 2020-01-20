<?php declare(strict_types=1);

namespace UserBundleTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Token;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package UserBundleTests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use ControllerTestTrait;
    use DatabaseTestTrait;

    /**
     * @var NativePasswordEncoder
     */
    protected $encoder;

    /**
     * @var UserInterface
     */
    protected $loggedUser;

    /**
     * ControllerTestCaseAbstract constructor.
     *
     * @param string|null $name
     * @param mixed[]     $data
     * @param string      $dataName
     */
    public function __construct(?string $name = NULL, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->encoder = new NativePasswordEncoder(3);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->startClient();
        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
        $this->loggedUser = $this->loginUser('test@example.com', 'password');
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
        $password = $this->encoder->encodePassword($password, '');
        $user     = new User();
        $user
            ->setEmail($username)
            ->setPassword($password);

        $this->pfd($user);
        $this->setClientCookies(
            $user,
            $password,
            Token::class,
            SecurityManager::SECURITY_KEY,
            SecurityManager::SECURED_AREA
        );

        return $user;
    }

}
