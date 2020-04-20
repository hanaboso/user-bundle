<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Handler;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\TokenInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Symfony\Component\HttpFoundation\Request;
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
final class UserHandlerTest extends DatabaseTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @var UserHandler
     */
    private UserHandler $handler;

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::login
     */
    public function testLogin(): void
    {
        $this->createUser();

        $data = $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);

        self::assertEquals(['id' => $data->getId(), 'email' => 'user@example.com'], $data->toArray());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::loggedUser
     */
    public function testLoggedUser(): void
    {
        $this->createUser();

        $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);
        $data = $this->handler->loggedUser();

        self::assertEquals(['id' => $data->getId(), 'email' => 'user@example.com'], $data->toArray());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::logout
     */
    public function testLogout(): void
    {
        $this->createUser();

        $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);

        self::assertEquals([], $this->handler->logout());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::register
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
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::activate
     */
    public function testActivate(): void
    {
        $this->handler->register(['email' => 'user@example.com']);

        /** @var TokenInterface $token */
        $token = $this->dm->getRepository(Token::class)->findAll()[0];
        $this->handler->activate($token->getHash());

        $this->dm->clear();

        self::assertCount(0, $this->dm->getRepository(TmpUser::class)->findAll());
        self::assertCount(1, $this->dm->getRepository(User::class)->findAll());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::verify
     */
    public function testVerify(): void
    {
        $data = ['email' => 'user@example.com'];
        $this->handler->register($data);

        /** @var TokenInterface $token */
        $token = $this->dm->getRepository(Token::class)->findAll()[0];
        $res   = $this->handler->verify($token->getHash());

        self::assertEquals($data, $res);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::setPassword
     */
    public function testSetPassword(): void
    {
        $token = (new Token())->setUser($this->createUser());
        $this->pfd($token);

        self::assertEquals([], $this->handler->setPassword($token->getHash(), ['password' => 'anotherPassw0rd']));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::changePassword
     */
    public function testChangePassword(): void
    {
        $this->createUser();

        $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);

        self::assertEquals([], $this->handler->changePassword(['password' => 'anotherPassw0rd']));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::resetPassword
     */
    public function testResetPassword(): void
    {
        $this->createUser();

        self::assertEquals([], $this->handler->resetPassword(['email' => 'user@example.com']));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::delete
     */
    public function testDelete(): void
    {
        $this->createUser();
        $user = $this->createUser();

        $this->handler->login(['email' => 'user@example.com', 'password' => 'passw0rd']);

        self::assertEquals(
            [
                'id'    => $user->getId(),
                'email' => 'user@example.com',
            ],
            $this->handler->delete($user->getId())->toArray()
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::delete
     */
    public function testDeleteException(): void
    {
        self::expectException(UserManagerException::class);
        self::expectExceptionCode(UserManagerException::USER_NOT_EXISTS);
        self::expectExceptionMessage('User with id [Unknown] not found.');

        $this->handler->delete('Unknown');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::onLogoutSuccess
     */
    public function testOnLogoutSuccess(): void
    {
        self::assertEquals('{}', $this->handler->onLogoutSuccess(new Request())->getContent());
    }

    /**
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::getSubscribedEvents
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
            UserHandler::getSubscribedEvents()
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::onCoreException
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
     *
     * @covers \Hanaboso\UserBundle\Handler\UserHandler::onCoreException
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

        $this->handler = self::$container->get('hbpf.user.handler.user');
    }

    /**
     * @return UserInterface
     * @throws Exception
     */
    private function createUser(): UserInterface
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setPassword('$2y$12$E66ihLcqEwlM018HA.rD3.eeC/79zzP3L5W9hpT19.YHqPBC3EQFe');
        $this->pfd($user);

        return $user;
    }

}
