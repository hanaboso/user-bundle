<?php declare(strict_types=1);

namespace Tests\Controller;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Nette\Utils\Strings;
use Tests\ControllerTestCaseAbstract;

/**
 * Class UserControllerTest
 *
 * @package Tests\Controller
 */
final class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    protected function setUp(): void
    {
        // Intentionally not calling parent setUp
        $this->client = self::createClient([], []);
        $this->dm->getConnection()->dropDatabase('pipes');
    }

    /**
     *
     */
    public function testLogin(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('/user/login', [
            'email'    => $user->getEmail(),
            'password' => 'passw0rd',
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertEquals($user->getEmail(), $response->content->email);
    }

    /**
     *
     */
    public function testLoginNotFoundEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('/user/login', [
            'email'    => '',
            'password' => '',
        ]);

        $this->assertEquals(400, $response->status);
        $content = $response->content;
        $this->assertEquals(SecurityManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testLoginNotFoundPassword(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('/user/login', [
            'email'    => $user->getEmail(),
            'password' => '',
        ]);

        $this->assertEquals(400, $response->status);
        $content = $response->content;
        $this->assertEquals(SecurityManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testLoggedUser(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $loginResponse = $this->sendPost('/user/login', [
            'email'    => $user->getEmail(),
            'password' => 'passw0rd',
        ]);

        $this->assertEquals(200, $loginResponse->status);
        $this->assertEquals($user->getEmail(), $loginResponse->content->email);

        $loggedResponse = $this->sendGet('/user/logged_user');

        $this->assertEquals(200, $loggedResponse->status);
        $this->assertEquals($user->getEmail(), $loggedResponse->content->email);
    }

    /**
     *
     */
    public function testLoggedUserNotLogged(): void
    {
        $response = $this->sendGet('/user/logged_user');

        $this->assertEquals(401, $response->status);
        $this->assertEquals([
            'status'     => 'ERROR',
            'error_code' => 0,
            'type'       => 'Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException',
            'message'    => 'User not logged!',
        ], (array) $response->content);
    }

    /**
     *
     */
    public function testLogout(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $response = $this->sendPost('/user/logout', []);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testRegister(): void
    {
        $this->prepareMailerMock();

        $response = $this->sendPost('/user/register', [
            'email' => 'email@example.com',
        ]);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testRegisterNotUniqueEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $response = $this->sendPost('/user/register', [
            'email' => 'email@example.com',
        ]);

        $this->assertEquals(400, $response->status);
        $content = $response->content;
        $this->assertEquals(UserManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testActivate(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setTmpUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/user/%s/activate', $token->getHash()), []);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testActivateNotValid(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setTmpUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/user/%s/activate', Strings::substring($token->getHash(), 1)),
            []);

        $this->assertEquals(400, $response->status);
        $content = $response->content;
        $this->assertEquals(TokenManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testSetPassword(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/user/%s/set_password', $token->getHash()),
            ['password' => 'newPassword']);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testSetPasswordNotValid(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost(sprintf('/user/%s/set_password',
            Strings::substring($token->getHash(), 1)), ['password' => 'newPassword']);

        $this->assertEquals(500, $response->status);
        $content = $response->content;
        $this->assertEquals(TokenManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     * @throws Exception
     */
    public function testChangePassword(): void
    {
        $user     = $this->loginUser('email@example.com', 'passw0rd');
        $response = $this->sendPost('/user/change_password', ['password' => 'anotherPassw0rd']);

        $this->assertEquals(200, $response->status);
        $this->dm->clear();
        $existingUser = $this->dm->getRepository(User::class)->find($user->getId());
        $this->assertNotSame($user->getPassword(), $existingUser->getPassword());
    }

    /**
     *
     */
    public function testChangePasswordNotLogged(): void
    {
        $response = $this->sendPost('/user/change_password', ['password' => 'anotherPassw0rd']);

        $this->assertEquals([
            'status'     => 'ERROR',
            'error_code' => 0,
            'type'       => 'Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException',
            'message'    => 'User not logged!',
        ], (array) $response->content);
    }

    /**
     *
     */
    public function testResetPassword(): void
    {
        $this->prepareMailerMock();
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost('/user/reset_password', [
            'email' => $user->getEmail(),
        ]);

        $this->assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testResetPasswordNotFoundEmail(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $token = (new Token())->setUser($user);
        $this->persistAndFlush($token);

        $response = $this->sendPost('/user/reset_password', [
            'email' => '',
        ]);
        $content  = $response->content;

        $this->assertEquals(400, $response->status);
        $this->assertEquals(UserManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testDelete(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->persistAndFlush($user);
        $this->dm->flush();

        $response = $this->sendDelete(sprintf('/user/%s/delete', $user->getId()));

        $this->assertEquals(200, $response->status);
        $this->assertEquals($user->getEmail(), $response->content->email);
    }

    /**
     *
     */
    public function testDeleteMissing(): void
    {
        $this->loginUser('email@example.com', 'passw0rd');

        $response = $this->sendDelete('/user/0/delete');
        $content  = $response->content;

        $this->assertEquals(500, $response->status);
        $this->assertEquals(UserManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testDeleteYourself(): void
    {
        $loggedUser = $this->loginUser('email@example.com', 'passw0rd');

        $response = $this->sendDelete(sprintf('/user/%s/delete', $loggedUser->getId()));
        $content  = $response->content;

        $this->assertEquals(500, $response->status);
        $this->assertEquals(UserManagerException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    private function prepareMailerMock(): void
    {
        $this->client->getContainer()->set('hbpf.user.mailer', $this->createMock(Mailer::class));
    }

}