<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Model\Security;

use Doctrine\Common\Persistence\ObjectRepository;
use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Repository\Document\UserRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
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
     * @var Session<mixed>
     */
    private Session $session;

    /**
     * @var PasswordEncoderInterface
     */
    private PasswordEncoderInterface $encoder;

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
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $user = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->encoder->isPasswordValid($user->getPassword() ?? '', 'passw0rd', ''));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::login
     */
    public function testLoginLogged(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $user = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->encoder->isPasswordValid($user->getPassword() ?? '', 'passw0rd', ''));

        $user = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->encoder->isPasswordValid($user->getPassword() ?? '', 'passw0rd', ''));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::login
     */
    public function testLoginLoggedException(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $user = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->encoder->isPasswordValid($user->getPassword() ?? '', 'passw0rd', ''));

        $repository = self::createMock(UserRepository::class);
        $repository->method('find')->willReturn(NULL);
        $this->setProperty($this->securityManager, 'userRepository', $repository);

        self::expectException(SecurityManagerException::class);
        self::expectExceptionCode(SecurityManagerException::USER_NOT_LOGGED);
        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::login
     */
    public function testLoginInvalidEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

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
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        self::expectException(SecurityManagerException::class);
        self::expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);
        $this->securityManager->login(['email' => 'no-email@example.com', 'password' => 'invalidPassw0rd']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::isLoggedIn
     */
    public function testIsLoggedIn(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        self::assertTrue(
            $this->session->has(
                sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
            )
        );

        $token = unserialize(
            $this->session->get(sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA))
        );
        /** @var User $user */
        $user = $this->userRepository->find($token->getUser()->getId());
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::isLoggedIn
     */
    public function testIsLoggedOut(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        self::assertFalse(
            $this->session->has(
                sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
            )
        );
        self::assertNull(
            $this->userRepository->find(
                $this->session->get(
                    sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
                )
            )
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::logout
     */
    public function testLogout(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        self::assertTrue(
            $this->session->has(
                sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
            )
        );

        $token = unserialize(
            $this->session->get(
                sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
            )
        );

        /** @var User $user */
        $user = $this->userRepository->find($token->getUser()->getId());
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));

        $this->securityManager->logout();
        self::assertFalse(
            $this->session->has(
                sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
            )
        );
        self::assertNull(
            $this->userRepository->find(
                $this->session->get(
                    sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
                )
            )
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::getLoggedUser
     */
    public function testGetLoggedUser(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);

        $user = $this->securityManager->getLoggedUser();
        self::assertEquals('email@example.com', $user->getEmail());
        self::assertTrue($this->encoder->isPasswordValid($user->getPassword() ?? '', 'passw0rd', ''));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::getLoggedUser
     */
    public function testGetNotLoggedUser(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->expectException(SecurityManagerException::class);
        $this->expectExceptionCode(SecurityManagerException::USER_NOT_LOGGED);
        $this->securityManager->getLoggedUser();
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Security\SecurityManager::encodePassword
     */
    public function testEncodePassword(): void
    {
        self::assertMatchesRegularExpression(
            '/\$argon2id\$v=19\$m=65536,t=3,p=1.{67}/',
            $this->securityManager->encodePassword('Passw0rd')
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
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session();
        $this->session->invalidate();
        $this->session->clear();
        $this->encoder         = new NativePasswordEncoder(3);
        $encodeFactory         = new EncoderFactory([$this->encoder]);
        $this->securityManager = new SecurityManager(
            self::$container->get('hbpf.database_manager_locator'),
            $encodeFactory,
            $this->session,
            self::$container->get('security.token_storage'),
            self::$container->get('hbpf.user.provider.resource')
        );
        $this->userRepository  = $this->dm->getRepository(User::class);
    }

}
