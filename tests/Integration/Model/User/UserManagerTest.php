<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Model\User;

use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class UserManagerTest
 *
 * @package UserBundleTests\Integration\Model\User
 * @ORM\Entity
 * @ORM\Table(name="user_manager_test")
 */
final class UserManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ObjectRepository<User>
     */
    private $userRepository;

    /**
     * @var ObjectRepository<TmpUser>
     */
    private $tmpUserRepository;

    /**
     * @var ObjectRepository<Token>
     */
    private $tokenRepository;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->userManager       = self::$container->get('hbpf.user.manager.user');
        $this->userRepository    = $this->dm->getRepository(User::class);
        $this->tmpUserRepository = $this->dm->getRepository(TmpUser::class);
        $this->tokenRepository   = $this->dm->getRepository(Token::class);
        $this->encoder           = new NativePasswordEncoder(3);
    }

    /**
     * @covers UserManager::register()
     * @throws Exception
     */
    public function testRegister(): void
    {
        $this->prepareMailerMock();
        $this->userManager->register(['email' => 'email@example.com']);

        /** @var TmpUser[] $tmpUsers */
        $tmpUsers = $this->tmpUserRepository->findBy(['email' => 'email@example.com']);
        $this->assertEquals(1, count($tmpUsers));
        $this->assertInstanceOf(TmpUser::class, $tmpUsers[0]);

        /** @var Token[] $tokens */
        $tokens = $this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $tmpUsers[0]]);
        $this->assertEquals(1, count($tokens));
        $this->assertInstanceOf(Token::class, $tokens[0]);
        $this->assertInstanceOf(TmpUser::class, $tokens[0]->getTmpUser());
        $this->assertEquals('email@example.com', $tokens[0]->getTmpUser()->getEmail());
    }

    /**
     * @covers UserManager::register()
     * @throws Exception
     */
    public function testRegisterMultiple(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->testRegister();
        }
    }

    /**
     * @covers UserManager::register()
     * @throws Exception
     */
    public function testRegisterInvalidEmail(): void
    {
        $this->pfd((new User())->setEmail('email@example.com'));

        $this->expectException(UserManagerException::class);
        $this->expectExceptionCode(UserManagerException::USER_EMAIL_ALREADY_EXISTS);
        $this->userManager->register(['email' => 'email@example.com']);
    }

    /**
     * @covers UserManager::activate()
     * @throws Exception
     */
    public function testActivate(): void
    {
        $tmpUser = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($tmpUser);

        $token = (new Token())->setTmpUser($tmpUser);
        $this->pfd($token);

        /** @var User[] $users */
        $users = $this->userRepository->findBy(['email' => 'email@example.com']);
        /** @var TmpUser[] $tmpUsers */
        $tmpUsers = $this->tmpUserRepository->findBy(['email' => 'email@example.com']);

        $this->assertEquals(0, count($users));
        $this->assertEquals(1, count($tmpUsers));
        $this->assertInstanceOf(TmpUser::class, $tmpUsers[0]);
        $this->assertEquals('email@example.com', $tmpUsers[0]->getEmail());

        $this->userManager->activate($token->getHash());

        $users    = $this->userRepository->findBy(['email' => 'email@example.com']);
        $tmpUsers = $this->tmpUserRepository->findBy(['email' => 'email@example.com']);

        $this->assertEquals(0, count($tmpUsers));
        $this->assertEquals(1, count($users));
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertEquals('email@example.com', $users[0]->getEmail());
    }

    /**
     * @covers UserManager::activate()
     * @throws Exception
     */
    public function testActivateNotValid(): void
    {
        $tmpUser = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($tmpUser);

        $token = (new Token())->setTmpUser($tmpUser);
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));
        $this->pfd($token);

        $this->expectException(TokenManagerException::class);
        $this->expectExceptionCode(TokenManagerException::TOKEN_NOT_VALID);
        $this->userManager->activate($token->getHash());
    }

    /**
     * @covers UserManager::resetPassword()
     * @throws Exception
     */
    public function testResetPassword(): void
    {
        $this->prepareMailerMock();
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $this->userManager->resetPassword(['email' => 'email@example.com']);

        /** @var Token[] $tokens */
        $tokens = $this->tokenRepository->findBy(['user' => $user]);
        $this->assertEquals(1, count($tokens));
        $this->assertInstanceOf(Token::class, $tokens[0]);
        $this->assertEquals('email@example.com', $tokens[0]->getUserOrTmpUser()->getEmail());
    }

    /**
     * @covers UserManager::setPassword()
     * @throws Exception
     */
    public function testSetPassword(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $this->pfd($token);

        $this->userManager->setPassword($token->getHash(), ['password' => 'passw0rd']);

        /** @var User[] $users */
        $users = $this->userRepository->findBy(['email' => 'email@example.com']);

        $this->assertEquals(1, count($users));
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertEquals('email@example.com', $users[0]->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($users[0]->getPassword(), 'passw0rd', ''));
    }

    /**
     * @covers UserManager::setPassword()
     * @throws Exception
     */
    public function testSetPasswordNotValid(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));
        $this->pfd($token);

        $this->expectException(TokenManagerException::class);
        $this->expectExceptionCode(TokenManagerException::TOKEN_NOT_VALID);
        $this->userManager->setPassword($token->getHash(), ['password' => 'passw0rd']);
    }

    /**
     * @throws Exception
     */
    private function prepareMailerMock(): void
    {
        $this->userManager = new UserManager(
            self::$container->get('hbpf.database_manager_locator'),
            self::$container->get('hbpf.user.manager.security'),
            self::$container->get('hbpf.user.manager.token'),
            self::$container->get('event_dispatcher'),
            self::$container->get('hbpf.user.provider.resource'),
            $this->createMock(Mailer::class),
            'host',
            'active-link',
            'password-link'
        );
    }

}
