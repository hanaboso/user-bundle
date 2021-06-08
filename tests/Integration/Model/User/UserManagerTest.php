<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Model\User;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use EmailServiceBundle\Exception\MailerException;
use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class UserManagerTest
 *
 * @package UserBundleTests\Integration\Model\User
 *
 * @covers  \Hanaboso\UserBundle\Model\User\UserManager
 */
final class UserManagerTest extends DatabaseTestCaseAbstract
{

    use CustomAssertTrait;

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
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::login
     */
    public function testLogin(): void
    {
        $this->createUser('user@example.com');
        $this->injectJwt('');
        [$user,] = $this->userManager->login(['email' => 'user@example.com', 'password' => 'passw0rd']);

        self::assertEquals(['id' => $user->getId(), 'email' => 'user@example.com'], $user->toArray());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::loggedUser
     */
    public function testLoggedUser(): void
    {
        $this->createUser('user@example.com');
        $this->injectJwt('');
        [, $token] = $this->userManager->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($token);
        $user = $this->userManager->loggedUser();

        self::assertEquals(['id' => $user->getId(), 'email' => 'user@example.com'], $user->toArray());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::logout
     */
    public function testLogout(): void
    {
        $this->createUser('user@example.com');
        $this->injectJwt('');
        [, $token] = $this->userManager->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($token);
        $this->userManager->logout();

        self::assertFake();
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::register
     */
    public function testRegister(): void
    {
        $this->prepareMailerMock();
        $this->userManager->register(['email' => 'email@example.com']);

        /** @var TmpUser[] $tmpUsers */
        $tmpUsers = $this->tmpUserRepository->findBy(['email' => 'email@example.com']);
        self::assertCount(1, $tmpUsers);

        /** @var Token[] $tokens */
        $tokens = $this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $tmpUsers[0]]);
        /** @var TmpUser $tmpUser */
        $tmpUser = $tokens[0]->getTmpUser();
        self::assertCount(1, $tokens);
        self::assertEquals('email@example.com', $tmpUser->getEmail());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::register
     */
    public function testRegisterMultiple(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->testRegister();
        }
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::register
     */
    public function testRegisterInvalidEmail(): void
    {
        $this->pfd((new User())->setEmail('email@example.com'));

        $this->userManager->register(['email' => 'email@example.com']);
        self::assertFake();
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::register
     */
    public function testRegisterException(): void
    {
        $this->prepareMailerMock();

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist')->willThrowException(new ORMException());
        $manager = clone $this->userManager;
        $this->setProperty($manager, 'dm', $dm);

        self::expectException(UserManagerException::class);
        $manager->register(['email' => 'email@example.com']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::activate
     */
    public function testActivate(): void
    {
        /** @var TmpUser $tmpUser */
        $tmpUser = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($tmpUser);

        $token = (new Token())->setTmpUser($tmpUser);
        $this->pfd($token);

        /** @var User[] $users */
        $users = $this->userRepository->findBy(['email' => 'email@example.com']);
        /** @var TmpUser[] $tmpUsers */
        $tmpUsers = $this->tmpUserRepository->findBy(['email' => 'email@example.com']);

        self::assertCount(0, $users);
        self::assertCount(1, $tmpUsers);
        self::assertEquals('email@example.com', $tmpUsers[0]->getEmail());

        $this->userManager->activate($token->getHash());

        $users    = $this->userRepository->findBy(['email' => 'email@example.com']);
        $tmpUsers = $this->tmpUserRepository->findBy(['email' => 'email@example.com']);

        self::assertCount(0, $tmpUsers);
        self::assertCount(1, $users);
        self::assertEquals('email@example.com', $users[0]->getEmail());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::activate
     */
    public function testActivateNotValid(): void
    {
        /** @var TmpUser $tmpUser */
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
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::activate
     */
    public function testActivateNoTmpUser(): void
    {
        $token = new Token();
        $this->pfd($token);

        self::expectException(TokenManagerException::class);
        self::expectExceptionCode(TokenManagerException::TOKEN_ALREADY_USED);
        self::expectExceptionMessage('Token has already been used.');

        $this->userManager->activate($token->getHash());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::activate
     */
    public function testActivateException(): void
    {
        /** @var TmpUser $tmpUser */
        $tmpUser = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($tmpUser);

        $token = (new Token())->setTmpUser($tmpUser);
        $this->pfd($token);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist')->willThrowException(new ORMException());
        $manager = clone $this->userManager;
        $this->setProperty($manager, 'dm', $dm);

        self::expectException(UserManagerException::class);
        $manager->activate($token->getHash());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::verify
     */
    public function testVerify(): void
    {
        /** @var TmpUser $tmpUser */
        $tmpUser = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($tmpUser);

        $token = (new Token())->setTmpUser($tmpUser);
        $this->pfd($token);

        /** @var TmpUser[] $tmpUsers */
        $tmpUsers = $this->tmpUserRepository->findBy(['email' => 'email@example.com']);

        self::assertCount(1, $tmpUsers);
        self::assertEquals('email@example.com', $tmpUsers[0]->getEmail());

        $this->dm->clear();
        $res = $this->userManager->verify($token->getHash());

        self::assertEquals($tmpUsers[0]->getEmail(), $res->getEmail());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::resetPassword
     */
    public function testResetPassword(): void
    {
        $this->prepareMailerMock();
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $this->userManager->resetPassword(['email' => 'email@example.com']);

        /** @var Token[] $tokens */
        $tokens = $this->tokenRepository->findBy(['user' => $user]);
        self::assertCount(1, $tokens);
        self::assertEquals('email@example.com', $tokens[0]->getUserOrTmpUser()->getEmail());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::resetPassword
     */
    public function testResetPasswordException2(): void
    {
        $this->prepareMailerMock();
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $mailer = $this->createMock(Mailer::class);
        $mailer->method('send')->willThrowException(new MailerException());
        $manager = clone $this->userManager;
        $this->setProperty($manager, 'mailer', $mailer);

        self::expectException(UserManagerException::class);
        $manager->resetPassword(['email' => 'email@example.com']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::resetPassword
     */
    public function testResetPasswordException(): void
    {
        $this->userManager->resetPassword(['email' => 'Unknown']);
        self::assertFake();
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::setPassword
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

        self::assertCount(1, $users);
        self::assertEquals('email@example.com', $users[0]->getEmail());
        self::assertTrue($this->getEncoder()->verify($users[0]->getPassword(), 'passw0rd'));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::setPassword
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
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::setPassword
     */
    public function testSetPasswordException(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $this->pfd($token);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('remove')->willThrowException(new ORMException());
        $manager = clone $this->userManager;
        $this->setProperty($manager, 'dm', $dm);

        self::expectException(UserManagerException::class);
        $manager->setPassword($token->getHash(), ['password' => 'passw0rd']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::changePassword
     */
    public function testChangePassword(): void
    {
        [, $jwt] = $this->loginUser();
        $this->injectJwt($jwt);
        $this->userManager->changePassword(['password' => 'Passw0rd', 'old_password' => 'passw0rd']);

        /** @var User[] $users */
        $users = $this->userRepository->findBy(['email' => 'email@example.com']);

        self::assertCount(1, $users);
        self::assertEquals('email@example.com', $users[0]->getEmail());
        self::assertTrue($this->getEncoder()->verify($users[0]->getPassword(), 'Passw0rd'));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::changePassword
     */
    public function testChangePasswordException(): void
    {
        [, $jwt] = $this->loginUser('user@example.com');
        $this->injectJwt($jwt);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('flush')->willThrowException(new ORMException());
        $manager = clone $this->userManager;
        $this->setProperty($manager, 'dm', $dm);

        self::expectException(UserManagerException::class);
        $manager->changePassword(['password' => 'Passw0rd', 'old_password' => 'passw0rd']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::delete
     */
    public function testDelete(): void
    {
        [, $jwt] = $this->loginUser('user@example.com');
        $this->injectJwt($jwt);
        $user = $this->createUser();
        $this->userManager->delete($user);

        /** @var User[] $users */
        $users = $this->userRepository->findAll();

        self::assertCount(2, $users);
        self::assertFalse($users[0]->isDeleted());
        self::assertTrue($users[1]->isDeleted());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::delete
     */
    public function testDeleteException2(): void
    {
        [, $jwt] = $this->loginUser('user@example.com');
        $this->injectJwt($jwt);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('flush')->willThrowException(new ORMException());
        $manager = clone $this->userManager;
        $this->setProperty($manager, 'dm', $dm);

        self::expectException(UserManagerException::class);
        $manager->delete($this->createUser());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\User\UserManager::delete
     */
    public function testDeleteException(): void
    {
        self::expectException(UserManagerException::class);
        self::expectExceptionCode(UserManagerException::USER_DELETE_NOT_ALLOWED);
        self::expectExceptionMessageMatches("/User '\w+' delete not allowed./");

        [$user, $jwt] = $this->loginUser('user@example.com');
        $this->injectJwt($jwt);
        $this->userManager->delete($user);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->userManager       = self::getContainer()->get('hbpf.user.manager.user');
        $this->userRepository    = $this->dm->getRepository(User::class);
        $this->tmpUserRepository = $this->dm->getRepository(TmpUser::class);
        $this->tokenRepository   = $this->dm->getRepository(Token::class);
    }

    /**
     * @throws Exception
     */
    private function prepareMailerMock(): void
    {
        $this->userManager = new UserManager(
            self::getContainer()->get('hbpf.database_manager_locator'),
            self::getContainer()->get('hbpf.user.manager.security'),
            self::getContainer()->get('hbpf.user.manager.token'),
            self::getContainer()->get('event_dispatcher'),
            self::getContainer()->get('hbpf.user.provider.resource'),
            $this->createMock(Mailer::class),
            'host',
            'active-link',
            'password-link',
        );
    }

}
