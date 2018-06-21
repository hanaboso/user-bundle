<?php declare(strict_types=1);

namespace Tests\Integration\Model\User;

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
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class UserManagerTest
 *
 * @package Tests\Integration\Model\User
 * @ORM\Entity
 * @ORM\Table(name="user_manager_test")
 */
final class UserManagerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ObjectRepository
     */
    private $userRepository;

    /**
     * @var ObjectRepository
     */
    private $tmpUserRepository;

    /**
     * @var ObjectRepository
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
        $this->userManager       = $this->c->get('hbpf.user.manager.user');
        $this->userRepository    = $this->dm->getRepository(User::class);
        $this->tmpUserRepository = $this->dm->getRepository(TmpUser::class);
        $this->tokenRepository   = $this->dm->getRepository(Token::class);
        $this->encoder           = new BCryptPasswordEncoder(12);
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
        $this->persistAndFlush((new User())->setEmail('email@example.com'));

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
        $this->persistAndFlush($tmpUser);

        $token = (new Token())->setTmpUser($tmpUser);
        $this->persistAndFlush($token);

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
        $this->persistAndFlush($tmpUser);

        $token = (new Token())->setTmpUser($tmpUser);
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));
        $this->persistAndFlush($token);

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
        $this->persistAndFlush($user);

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
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

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
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));
        $this->persistAndFlush($token);

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
            $this->c->get('hbpf.database_manager_locator'),
            $this->c->get('hbpf.user.manager.security'),
            $this->c->get('hbpf.user.manager.token'),
            $this->c->get('event_dispatcher'),
            $this->c->get('hbpf.user.provider.resource'),
            $this->createMock(Mailer::class),
            'active-link',
            'password-link'
        );
    }

}