<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Handler;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class UserHandlerTest
 *
 * @package UserBundleTests\Integration\Handler
 *
 * @covers  \Hanaboso\UserBundle\Handler\UserHandler
 */
#[CoversClass(UserHandler::class)]
final class UserHandlerTest extends DatabaseTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @var UserHandler
     */
    private UserHandler $handler;

    /**
     * @throws Exception
     */
    public function testLogin(): void
    {
        $this->createUser('user@example.com');

        $loggedUser = $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($loggedUser['token']);

        self::assertEquals(
            ['id' => $loggedUser['user']['id'], 'email' => 'user@example.com'],
            $loggedUser['user'],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoggedUser(): void
    {
        $this->createUser('user@example.com');

        $loggedUser = $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($loggedUser['token']);
        $loggedUser = $this->handler->loggedUser()['user'];

        self::assertEquals(['id' => $loggedUser['id'], 'email' => 'user@example.com'], $loggedUser);
    }

    /**
     * @throws Exception
     */
    public function testLogout(): void
    {
        $this->createUser('user@example.com');

        $loggedUser = $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($loggedUser['token']);

        self::assertEquals([], $this->handler->logout());
    }

    /**
     * @throws Exception
     */
    public function testRegister(): void
    {
        self::assertEquals([], $this->handler->register(['email' => 'user@example.com']));

        $this->dm->clear();

        self::assertCount(1, $this->dm->getRepository(TmpUser::class)->findAll());
        self::assertCount(0, $this->dm->getRepository(User::class)->findAll());
    }

    /**
     * @throws Exception
     */
    public function testActivate(): void
    {
        $this->handler->register(['email' => 'user@example.com']);

        /** @var Token $token */
        $token = $this->dm->getRepository(Token::class)->findAll()[0];
        $this->handler->activate($token->getHash());

        $this->dm->clear();

        self::assertCount(0, $this->dm->getRepository(TmpUser::class)->findAll());
        self::assertCount(1, $this->dm->getRepository(User::class)->findAll());
    }

    /**
     * @throws Exception
     */
    public function testVerify(): void
    {
        $data = ['email' => 'user@example.com'];
        $this->handler->register($data);

        /** @var Token $token */
        $token = $this->dm->getRepository(Token::class)->findAll()[0];
        $res   = $this->handler->verify($token->getHash());

        self::assertEquals($data, $res);
    }

    /**
     * @throws Exception
     */
    public function testSetPassword(): void
    {
        $token = new Token()->setUser($this->createUser());
        $this->pfd($token);

        self::assertEquals([], $this->handler->setPassword($token->getHash(), ['password' => 'anotherPassw0rd']));
    }

    /**
     * @throws Exception
     */
    public function testChangePassword(): void
    {
        $this->createUser('user@example.com');

        $loggedUser = $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($loggedUser['token']);

        self::assertEquals([], $this->handler->changePassword(['password' => 'anotherPassw0rd']));
    }

    /**
     * @throws Exception
     */
    public function testResetPassword(): void
    {
        $this->createUser('user@example.com');

        self::assertEquals([], $this->handler->resetPassword(['email' => 'user@example.com']));
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $testUser = $this->createUser('user1@example.com');
        $this->createUser('user@example.com');

        $loggedUser = $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $this->injectJwt($loggedUser['token']);

        self::assertEquals(
            [
                'email' => 'user1@example.com',
                'id'    => $testUser->getId(),
            ],
            $this->handler->delete($testUser->getId())->toArray(),
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteException(): void
    {
        self::expectException(UserManagerException::class);
        self::expectExceptionCode(UserManagerException::USER_NOT_EXISTS);
        self::expectExceptionMessage('User with id [Unknown] not found.');

        $this->handler->delete('Unknown');
    }

    /**
     * @return void
     */
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                'kernel.exception' => [
                    [
                        'onCoreException',
                        1_000,
                    ],
                ],
            ],
            UserHandler::getSubscribedEvents(),
        );
    }

    /**
     * @throws Exception
     */
    public function testOnCoreException(): void
    {
        $event = self::createMock(ExceptionEvent::class);
        $event->method('getThrowable')->willReturn(new AuthenticationException('Something gone wrong!'));

        $this->handler->onCoreException($event);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    public function testOnCoreExceptionSecond(): void
    {
        $event = self::createMock(ExceptionEvent::class);
        $event
            ->method('getThrowable')
            ->willReturn(new AuthenticationCredentialsNotFoundException('Something gone wrong!'));

        $this->handler->onCoreException($event);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::getContainer()->get('hbpf.user.handler.user');
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

}
