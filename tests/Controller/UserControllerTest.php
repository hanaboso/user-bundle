<?php declare(strict_types=1);

namespace UserBundleTests\Controller;

use Exception;
use Hanaboso\UserBundle\Controller\UserController;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use UserBundleTests\ControllerTestCaseAbstract;

/**
 * Class UserControllerTest
 *
 * @package UserBundleTests\Controller
 *
 * @covers  \Hanaboso\UserBundle\Controller\UserController
 */
final class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::loginAction
     */
    public function testLogin(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);
        $this->dm->clear();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/loginRequest.json', ['id' => 1]);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::loginAction
     */
    public function testLoginNotFoundEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedLoginRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::loginAction
     */
    public function testLoginNotFoundPassword(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedOnPassLoginRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::loginAction
     */
    public function testLoginMissingParameter(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserControllerTest/missingParameterLoginRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::loggedUserAction
     */
    public function testLoggedUser(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/loggedRequest.json', ['id' => 1]);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::loggedUserAction
     */
    public function testLoggedUserNotLogged(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->prepareHandlerMock('loggedUser', SecurityManagerException::class);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedLoggedRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::loggedUserAction
     */
    public function testLoggedUserException(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->prepareHandlerMock('loggedUser');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionLoggedRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::logoutAction
     */
    public function testLogout(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/logoutRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::logoutAction
     */
    public function testLogoutDirect(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        /** @var UserController $controller */
        $controller = self::$container->get('Hanaboso\UserBundle\Controller\UserController');
        $response   = $controller->logoutAction();

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::logoutAction
     */
    public function testLogoutDirectExceptionSecurity(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->prepareHandlerMock('logout', SecurityManagerException::class);

        /** @var UserController $controller */
        $controller = self::$container->get('Hanaboso\UserBundle\Controller\UserController');
        $response   = $controller->logoutAction();

        self::assertEquals(401, $response->getStatusCode());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::logoutAction
     */
    public function testLogoutDirectException(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->prepareHandlerMock('logout');

        /** @var UserController $controller */
        $controller = self::$container->get('Hanaboso\UserBundle\Controller\UserController');
        $response   = $controller->logoutAction();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::registerAction
     */
    public function testRegister(): void
    {
        $this->prepareMailerMock();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/registerRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::registerAction
     */
    public function testRegisterNotUniqueEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedRegisterRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::registerAction
     */
    public function testRegisterException(): void
    {
        $this->prepareHandlerMock('register');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionRegisterRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::activateAction
     */
    public function testActivate(): void
    {
        /** @var TmpUser $user */
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/activateRequest.json',
            [],
            ['token' => $token->getHash()]
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::activateAction
     */
    public function testActivateNotValid(): void
    {
        /** @var TmpUser $user */
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedActivateRequest.json', [], ['token' => '123']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::activateAction
     */
    public function testActivateException(): void
    {
        $this->prepareHandlerMock('activate');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionActivateRequest.json',
            [],
            ['token' => 'Unknown']
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::verifyAction
     */
    public function testVerify(): void
    {
        /** @var TmpUser $user */
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/verifyRequest.json',
            [],
            ['token' => $token->getHash()]
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::verifyAction
     */
    public function testVerifyNotValid(): void
    {
        /** @var TmpUser $user */
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedVerifyRequest.json', [], ['token' => '123']);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::verifyAction
     */
    public function testVerifyException(): void
    {
        $this->prepareHandlerMock('verify');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionVerifyRequest.json',
            [],
            ['token' => 'Unknown']
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::setPasswordAction
     */
    public function testSetPassword(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $this->pfd($token);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/setPasswordRequest.json',
            [],
            ['token' => $token->getHash()]
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::setPasswordAction
     */
    public function testSetPasswordNotValid(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $this->pfd($token);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/failedSetPasswordRequest.json',
            [],
            ['token' => '123']
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::setPasswordAction
     */
    public function testSetPasswordException(): void
    {
        $this->prepareHandlerMock('setPassword');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionSetPasswordRequest.json',
            [],
            ['token' => 'Unknown']
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::changePasswordAction
     */
    public function testChangePassword(): void
    {
        $user = $this->loginUser('email@example.com', 'passw0rd');
        $this->dm->clear();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/changePasswordRequest.json');
        /** @var User $existingUser */
        $existingUser = $this->dm->getRepository(User::class)->find($user->getId());
        self::assertNotSame($user->getPassword(), $existingUser->getPassword());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::changePasswordAction
     */
    public function testChangePasswordNotLogged(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->prepareHandlerMock('changePassword', SecurityManagerException::class);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedChangePasswordRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::changePasswordAction
     */
    public function testChangePasswordException(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->prepareHandlerMock('changePassword');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionChangePasswordRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::resetPasswordAction
     */
    public function testResetPassword(): void
    {
        $this->prepareMailerMock();
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/resetPasswordRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::resetPasswordAction
     */
    public function testResetPasswordNotFoundEmail(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedResetPasswordRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::resetPasswordAction
     */
    public function testResetPasswordException(): void
    {
        $this->prepareHandlerMock('resetPassword');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionResetPasswordRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::deleteAction
     */
    public function testDelete(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->pfd($user);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/deleteUserRequest.json',
            ['id' => '1'],
            ['id' => $user->getId()]
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::deleteAction
     */
    public function testDeleteMissing(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedDeleteUserRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::deleteAction
     */
    public function testDeleteException(): void
    {
        $this->prepareHandlerMock('delete');

        $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionDeleteUserRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Controller\UserController::deleteAction
     */
    public function testDeleteYourself(): void
    {
        $loggedUser = $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/deleteSelfRequest.json',
            ['message' => 'User \'self\' delete not allowed.'],
            ['id' => $loggedUser->getId()]
        );
    }

    /**
     * @throws Exception
     */

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        // Intentionally not calling parent setUp
        self::startClient();
        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
    }

    /**
     * ------------------------------------- HELPERS ------------------------------------------
     */

    /**
     *
     */
    private function prepareMailerMock(): void
    {
        self::$container->set('hbpf.user.mailer', self::createMock(Mailer::class));
    }

    /**
     * @param string $method
     * @param string $exception
     */
    private function prepareHandlerMock(string $method, string $exception = Exception::class): void
    {
        $handler = self::createMock(UserHandler::class);
        $handler->method($method)->willThrowException(new $exception('Something gone wrong!'));

        self::$container->set('hbpf.user.handler.user', $handler);
    }

}
