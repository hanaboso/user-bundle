<?php declare(strict_types=1);

namespace UserBundleTests\Controller;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use UserBundleTests\ControllerTestCaseAbstract;

/**
 * Class UserControllerTest
 *
 * @package UserBundleTests\Controller
 */
final class UserControllerTest extends ControllerTestCaseAbstract
{

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
     * @throws Exception
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
     */
    public function testLoggedUser(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/loggedRequest.json', ['id' => 1]);
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserNotLogged(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedLoggedRequest.json', ['id' => 1]);
    }

    /**
     * @throws Exception
     */
    public function testLogout(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/logoutRequest.json');
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
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->pfd($user);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedRegisterRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testActivate(): void
    {
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
     */
    public function testActivateNotValid(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($user);

        $token = (new Token())->setTmpUser($user);
        $this->pfd($token);

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedActivateRequest.json', [], ['token' => '123']);
    }

    /**
     * @throws Exception
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
     */
    public function testChangePassword(): void
    {
        $user = $this->loginUser('email@example.com', 'passw0rd');
        $this->dm->clear();

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/changePasswordRequest.json');
        /** @var User $existingUser */
        $existingUser = $this->dm->getRepository(User::class)->find($user->getId());
        $this->assertNotSame($user->getPassword(), $existingUser->getPassword());
    }

    /**
     *
     */
    public function testChangePasswordNotLogged(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedChangePasswordRequest.json');
    }

    /**
     * @throws Exception
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
     */
    public function testDeleteMissing(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $this->assertResponse(__DIR__ . '/data/UserControllerTest/failedDeleteUserRequest.json');
    }

    /**
     * @throws Exception
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
     * ------------------------------------- HELPERS ------------------------------------------
     */

    /**
     * @throws Exception
     */
    private function prepareMailerMock(): void
    {
        self::$container->set('hbpf.user.mailer', $this->createMock(Mailer::class));
    }

}
