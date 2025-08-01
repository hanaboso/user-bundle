<?php declare(strict_types=1);

namespace UserBundleTests\Controller;

use Exception;
use Hanaboso\UserBundle\Controller\UserController;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Handler\UserHandler;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Security\JWTAuthenticator;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\BrowserKit\Cookie;
use UserBundleTests\ControllerTestCaseAbstract;

/**
 * Class UserControllerTest
 *
 * @package UserBundleTests\Controller
 */
#[CoversClass(UserController::class)]
#[CoversClass(JWTAuthenticator::class)]
#[CoversClass(SecurityManager::class)]
final class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testLogin(): void
    {
        $this->createUser();

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/loginRequest.json',
            ['id' => 1, 'token' => 'JWToken'],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoginNotFoundEmail(): void
    {
        $this->createUser();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedLoginRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testLoginNotFoundPassword(): void
    {
        $this->createUser();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedOnPassLoginRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testLoginMissingParameter(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserControllerTest/missingParameterLoginRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testLoggedUser(): void
    {
        [, $jwt] = $this->loginUser();

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/loggedRequest.json',
            ['id' => 1, 'token' => 'jwt'],
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserExpiredAccessToken(): void
    {
        [, $jwt] = $this->loginUser('email@example.com', 'passw0rd', 0);
        sleep(2);
        [, $jwtRefresh] = $this->loginUser();

        $cookie = new Cookie('refreshToken', $jwtRefresh);
        $this->client->getCookieJar()->set($cookie);
        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/loggedExpiredTokenRequest.json',
            ['id' => 1, 'token' => 'jwt'],
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserExpiredAccessTokenAndRefreshToken(): void
    {
        [, $jwt] = $this->loginUser('email@example.com', 'passw0rd', 0);
        sleep(2);

        $cookie = new Cookie('refreshToken', $jwt);
        $this->client->getCookieJar()->set($cookie);
        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/loggedExpiredTokensRequest.json',
            ['id' => 1, 'token' => 'jwt'],
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserNotLogged(): void
    {
        $this->loginUser();

        $this->prepareHandlerMock('loggedUser', SecurityManagerException::class);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedLoggedRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserException(): void
    {
        $this->loginUser();

        $this->prepareHandlerMock('loggedUser');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionLoggedRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserNotLoggedError(): void
    {
        [, $jwt] = $this->loginUser();

        $this->prepareHandlerMock('loggedUser', SecurityManagerException::class);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/failedLoggedErrorRequest.json',
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserExceptionError(): void
    {
        [, $jwt] = $this->loginUser();

        $this->prepareHandlerMock('loggedUser');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionLoggedErrorRequest.json',
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserException2(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionLoggedRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testLogout(): void
    {
        [, $jwt] = $this->loginUser();

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/logoutRequest.json',
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testLogoutDirectExceptionSecurity(): void
    {
        $this->loginUser();

        $this->prepareHandlerMock('logout', SecurityManagerException::class);

        /** @var UserController $controller */
        $controller = self::getContainer()->get('Hanaboso\UserBundle\Controller\UserController');
        $response   = $controller->logoutAction();

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testLogoutDirectException(): void
    {
        $this->loginUser();

        $this->prepareHandlerMock('logout');

        /** @var UserController $controller */
        $controller = self::getContainer()->get('Hanaboso\UserBundle\Controller\UserController');
        $response   = $controller->logoutAction();

        self::assertSame(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testRegister(): void
    {
        $this->prepareMailerMock();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/registerRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testRegisterNotUniqueEmail(): void
    {
        $this->createUser();

        $this->prepareHandlerMock('register', UserManagerException::class);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedRegisterRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testRegisterException(): void
    {
        $this->prepareHandlerMock('register');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionRegisterRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testActivate(): void
    {
        $user = new TmpUser();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/activateRequest.json',
            [],
            ['token' => $token->getHash()],
        );
    }

    /**
     * @throws Exception
     */
    public function testActivateNotValid(): void
    {
        $user = new TmpUser();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedActivateRequest.json', [], ['token' => '123']);
    }

    /**
     * @throws Exception
     */
    public function testActivateException(): void
    {
        $this->prepareHandlerMock('activate');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionActivateRequest.json',
            [],
            ['token' => 'Unknown'],
        );
    }

    /**
     * @throws Exception
     */
    public function testVerify(): void
    {
        $user = new TmpUser();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setTmpUser($user);
        $this->pfd($token);
        $this->dm->clear();

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/verifyRequest.json',
            [],
            ['token' => $token->getHash()],
        );
    }

    /**
     * @throws Exception
     */
    public function testVerifyNotValid(): void
    {
        $user = new TmpUser();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedVerifyRequest.json', [], ['token' => '123']);
    }

    /**
     * @throws Exception
     */
    public function testVerifyException(): void
    {
        $this->prepareHandlerMock('verify');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionVerifyRequest.json',
            [],
            ['token' => 'Unknown'],
        );
    }

    /**
     * @throws Exception
     */
    public function testSetPassword(): void
    {
        $this->loginUser();

        $user = new User();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setUser($user);
        $this->pfd($token);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/setPasswordRequest.json',
            [],
            ['token' => $token->getHash()],
        );
    }

    /**
     * @throws Exception
     */
    public function testSetPasswordNotValid(): void
    {
        $this->loginUser();

        $user = new User();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setUser($user);
        $this->pfd($token);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/failedSetPasswordRequest.json',
            [],
            ['token' => '123'],
        );
    }

    /**
     * @throws Exception
     */
    public function testSetPasswordException(): void
    {
        $this->prepareHandlerMock('setPassword');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionSetPasswordRequest.json',
            [],
            ['token' => 'Unknown'],
        );
    }

    /**
     * @throws Exception
     */
    public function testChangePassword(): void
    {
        [$user, $jwt] = $this->loginUser();
        $this->dm->clear();

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/changePasswordRequest.json',
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
        /** @var User $existingUser */
        $existingUser = $this->dm->getRepository(User::class)->find($user->getId());
        self::assertNotSame($user->getPassword(), $existingUser->getPassword());
    }

    /**
     * @throws Exception
     */
    public function testChangePasswordNotLogged(): void
    {
        [, $jwt] = $this->loginUser();

        $this->prepareHandlerMock('changePassword', SecurityManagerException::class);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/failedChangePasswordRequest.json',
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testChangePasswordException(): void
    {
        [, $jwt] = $this->loginUser();

        $this->prepareHandlerMock('changePassword');

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionChangePasswordRequest.json',
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testResetPassword(): void
    {
        $this->prepareMailerMock();
        $this->loginUser();

        $user = new User();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/resetPasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testResetPasswordNotFoundEmail(): void
    {
        $this->loginUser();

        $user = new User();
        $user->setEmail('email@example.com');
        $this->pfd($user);

        $token = new Token()->setUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedResetPasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testResetPasswordException(): void
    {
        $this->prepareHandlerMock('resetPassword');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionResetPasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testResetPasswordErrorException(): void
    {
        $this->prepareHandlerMock('resetPassword', UserManagerException::class);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/exceptionResetPasswordErrorRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        [, $jwt] = $this->loginUser();

        $user = new User()
            ->setPassword('passw0rd')
            ->setEmail('email@example.com');
        $this->pfd($user);

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/deleteUserRequest.json',
            ['id' => '1'],
            ['id' => $user->getId()],
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteMissing(): void
    {
        $this->loginUser();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedDeleteUserRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testDeleteException(): void
    {
        $this->prepareHandlerMock('delete');

        [, $jwt] = $this->loginUser();

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/exceptionDeleteUserRequest.json',
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteYourself(): void
    {
        [$loggedUser, $jwt] = $this->loginUser();

        $this->assertResponse(
            __DIR__ . '/data/UserControllerTest/deleteSelfRequest.json',
            ['message' => 'User \'self\' delete not allowed.'],
            ['id' => $loggedUser->getId()],
            requestHeadersReplacements: [self::$AUTHORIZATION => $jwt],
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
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
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
        self::getContainer()->set('hbpf.user.mailer', self::createMock(Mailer::class));
    }

    /**
     * @param string $method
     * @param string $exception
     */
    private function prepareHandlerMock(string $method, string $exception = Exception::class): void
    {
        $handler = self::createMock(UserHandler::class);
        $handler->method($method)->willThrowException(new $exception('Something gone wrong!'));

        self::getContainer()->set('hbpf.user.handler.user', $handler);
    }

}
