<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Model\Security;

use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Repository\Document\UserRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Validation\Validator;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class SecurityManagerTest
 *
 * @package UserBundleTests\Integration\Model\Security
 *
 * @covers  \Hanaboso\UserBundle\Model\Security\SecurityManager
 */
final class SecurityManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var SecurityManager
     */
    private SecurityManager $securityManager;

    /**
     * @var ObjectRepository<User>
     */
    private $userRepository;

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::login
     */
    public function testLogin(): void
    {
        $user = $this->createUser();
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->getEncoder()->verify($user->getPassword() ?? '', 'passw0rd'));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::login
     */
    public function testLoginLoggedException(): void
    {
        $user = $this->createUser();

        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->getEncoder()->verify($user->getPassword() ?? '', 'passw0rd'));

        $repository = self::createMock(UserRepository::class);
        $repository->method('find')->willReturn(NULL);
        $this->setProperty($this->securityManager, 'userRepository', $repository);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::login
     */
    public function testLoginInvalidEmail(): void
    {
        $this->createUser();

        self::expectException(SecurityManagerException::class);
        self::expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);
        $this->securityManager->login(['email' => 'invalidEmail@example.com', 'password' => 'passw0rd']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::login
     */
    public function testLoginInvalidPassword(): void
    {
        $this->createUser();

        self::expectException(SecurityManagerException::class);
        self::expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);
        $this->securityManager->login(['email' => 'no-email@example.com', 'password' => 'invalidPassw0rd']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::getLoggedUser
     */
    public function testGetLoggedUser(): void
    {
        $this->createUser();

        [, $jwt] = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($jwt);

        [$user,] = $this->securityManager->getLoggedUser();
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->getEncoder()->verify($user->getPassword() ?? '', 'passw0rd'));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::getLoggedUser
     */
    public function testGetLoggedUserException(): void
    {
        $this->createUser();

        $man = $this->createPartialMock(SecurityManager::class, ['jwtVerifyAccessToken']);
        $man->method('jwtVerifyAccessToken')->willThrowException(new LogicException('Token not valid'));
        self::getContainer()->set('hbpf.user.manager.security', $man);

        [, $jwt] = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($jwt);

        self::expectException(SecurityManagerException::class);
        $man->getLoggedUser();
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::getLoggedUser
     */
    public function testJwtNullException(): void
    {
        $this->createUser();

        $man = $this->createPartialMock(RequestStack::class, ['getCurrentRequest']);
        $man->method('getCurrentRequest')->willReturn(new Request(server: ['HTTP_Authorization' => '']));
        self::getContainer()->set('hbpf.user.manager.security', $man);

        self::expectException(SecurityManagerException::class);
        $this->securityManager->jwtVerifyAccessToken();
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::getLoggedUser
     */
    public function testJwtFailParseException(): void
    {
        $validator = $this->createMock(Validator::class);
        $validator->method('validate')->willReturn(FALSE);
        $conf = $this->createMock(Configuration::class);
        $conf->method('validator')->willReturn($validator);
        $this->setProperty($this->securityManager, 'configuration', $conf);
        $this->createUser();

        $man = $this->createPartialMock(RequestStack::class, ['getCurrentRequest']);
        $man->method('getCurrentRequest')->willReturn(
            new Request(server: ['HTTP_Authorization' => 'asdsadas.asd.asda']),
        );
        $this->setProperty($this->securityManager, 'requestStack', $man);

        self::expectException(SecurityManagerException::class);
        $this->securityManager->jwtVerifyAccessToken();
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::encodePassword
     */
    public function testEncodePassword(): void
    {
        self::assertMatchesRegularExpression(
            '/\$2y\$04\$.*/',
            $this->securityManager->encodePassword('Passw0rd'),
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::validateUser
     */
    public function testValidateUserException(): void
    {
        self::expectException(SecurityManagerException::class);
        self::expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);

        $this->securityManager->validateUser((new User())->setEmail('user@example.com'), ['password' => 'Passw0rd']);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     */
    protected function createUser(string $username = 'email@example.com', string $password = 'passw0rd'): User
    {
        $user = parent::createUser($username, $password);
        $this->injectJwt('');

        return $user;
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $encodeFactory         = new PasswordHasherFactory([User::class => $this->getEncoder()]);
        $this->securityManager = new SecurityManager(
            self::getContainer()->get('hbpf.database_manager_locator'),
            $encodeFactory,
            self::getContainer()->get('hbpf.user.provider.resource'),
            self::getContainer()->get('request_stack'),
            self::getContainer()->get('config.jwt'),
            'Lax',
        );
        $this->userRepository  = $this->dm->getRepository(User::class);
    }

}
