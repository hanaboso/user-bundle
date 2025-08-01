<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Model\Security;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Repository\Document\UserRepository;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\InvalidClaimException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class SecurityManagerTest
 *
 * @package UserBundleTests\Integration\Model\Security
 */
#[CoversClass(SecurityManager::class)]
final class SecurityManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var SecurityManager
     */
    private SecurityManager $securityManager;

    /**
     * @throws Exception
     */
    public function testLogin(): void
    {
        $user = $this->createUser();
        self::assertSame('email@example.com', $user->getEmail());
        self::assertTrue($this->getEncoder()->verify($user->getPassword(), 'passw0rd'));
    }

    /**
     * @throws Exception
     */
    public function testLoginLoggedException(): void
    {
        $user = $this->createUser();

        self::assertSame('email@example.com', $user->getEmail());
        self::assertTrue($this->getEncoder()->verify($user->getPassword(), 'passw0rd'));

        $repository = self::createMock(UserRepository::class);
        $repository->method('find')->willReturn(NULL);
        $this->setProperty($this->securityManager, 'userRepository', $repository);
    }

    /**
     * @throws Exception
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
     */
    public function testJwtFailParseException(): void
    {
        $claimCheckerManager = $this->createMock(ClaimCheckerManager::class);
        $claimCheckerManager->method('check')->willThrowException(new InvalidClaimException('', '', ''));
        $this->setProperty($this->securityManager, 'claimCheckerManager', $claimCheckerManager);
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
     */
    public function testValidateUserException(): void
    {
        self::expectException(SecurityManagerException::class);
        self::expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);

        $user = new User();
        $user->setEmail('email@example.com');
        $this->securityManager->validateUser($user, ['password' => 'Passw0rd']);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     * @throws Exception
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
            self::getContainer()->get('jws.builder'),
            self::getContainer()->get('jws.loader'),
            self::getContainer()->get('jwt.jwk'),
            self::getContainer()->get('jwt.manager.checker'),
            'Lax',
        );
    }

}
