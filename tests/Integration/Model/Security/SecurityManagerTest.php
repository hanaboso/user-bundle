<?php declare(strict_types=1);

namespace Tests\Integration\Model\Security;

use Doctrine\Common\Persistence\ObjectRepository;
use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SecurityManagerTest
 *
 * @package Tests\Integration\Model\Security
 */
final class SecurityManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    /**
     * @var ObjectRepository
     */
    private $userRepository;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->encoder         = new NativePasswordEncoder(12);
        $encodeFactory         = new EncoderFactory([$this->encoder]);
        $this->securityManager = new SecurityManager(
            $this->c->get('hbpf.database_manager_locator'),
            $encodeFactory,
            $this->session,
            $this->c->get('security.token_storage'),
            $this->c->get('hbpf.user.provider.resource')
        );
        $this->userRepository  = $this->dm->getRepository(User::class);
    }

    /**
     * @covers SecurityManager::login()
     * @throws Exception
     */
    public function testLogin(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $user = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));
    }

    /**
     * @covers SecurityManager::login()
     * @throws Exception
     */
    public function testLoginInvalidEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->expectException(SecurityManagerException::class);
        $this->expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);
        $this->securityManager->login(['email' => 'invalidEmail@example.com', 'password' => 'passw0rd']);
    }

    /**
     * @covers SecurityManager::login()
     * @throws Exception
     */
    public function testLoginInvalidPassword(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->expectException(SecurityManagerException::class);
        $this->expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);
        $this->securityManager->login(['email' => 'no-email@example.com', 'password' => 'invalidPassw0rd']);
    }

    /**
     * @covers SecurityManager::isLoggedIn()
     * @throws Exception
     */
    public function testIsLoggedIn(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->assertTrue(
            $this->session->has(
                sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA))
        );

        $token = unserialize(
            $this->session->get(sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA))
        );
        /** @var User $user */
        $user = $this->userRepository->find($token->getUser()->getId());
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));
    }

    /**
     * @throws Exception
     */
    public function testIsLoggedOut(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->assertFalse($this->session->has(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
        ));
        $this->assertNull($this->userRepository->find($this->session->get(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
        )));
    }

    /**
     * @covers SecurityManager::logout()
     * @throws Exception
     */
    public function testLogout(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->assertTrue($this->session->has(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
        ));

        $token = unserialize($this->session->get(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
        ));

        /** @var User $user */
        $user = $this->userRepository->find($token->getUser()->getId());
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));

        $this->securityManager->logout();
        $this->assertFalse($this->session->has(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
        ));
        $this->assertNull($this->userRepository->find($this->session->get(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA)
        )));
    }

    /**
     * @covers SecurityManager::getLoggedUser()
     * @throws Exception
     */
    public function testGetLoggedUser(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);

        $user = $this->securityManager->getLoggedUser();
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));
    }

    /**
     * @covers SecurityManager::getLoggedUser()
     * @throws Exception
     */
    public function testGetNotLoggedUser(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->expectException(SecurityManagerException::class);
        $this->expectExceptionCode(SecurityManagerException::USER_NOT_LOGGED);
        $this->securityManager->getLoggedUser();
    }

}
