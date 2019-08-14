<?php declare(strict_types=1);

namespace Tests\Integration\Model\Token;

use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\UserBundle\Model\Token\TokenManager;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class TokenManagerTest
 *
 * @package Tests\Integration\Model\Token
 */
final class TokenManagerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var ObjectRepository
     */
    private $tokenRepository;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager    = self::$container->get('hbpf.user.manager.token');
        $this->tokenRepository = $this->dm->getRepository(Token::class);
    }

    /**
     * @covers TokenManager::create()
     * @throws Exception
     */
    public function testCreateUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        /** @var UserInterface $tokenUser */
        $tokenUser = $token->getUser();
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));
        $this->assertEquals($user->getEmail(), $tokenUser->getEmail());
    }

    /**
     * @covers TokenManager::create()
     * @throws Exception
     */
    public function testCreateTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        /** @var UserInterface $tokenUser */
        $tokenUser = $token->getTmpUser();
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));
        $this->assertEquals($user->getEmail(), $tokenUser->getEmail());
    }

    /**
     * @covers TokenManager::validate()
     * @throws Exception
     */
    public function testValidateToken(): void
    {
        $token = new Token();
        $this->persistAndFlush($token);

        /** @var Token $token */
        $token = $this->tokenManager->validate($token->getHash());
        $this->assertInstanceOf(Token::class, $token);
    }

    /**
     * @covers TokenManager::validate()
     * @throws Exception
     */
    public function testValidateInvalidToken(): void
    {
        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));
        $this->persistAndFlush($token);

        $this->expectException(TokenManagerException::class);
        $this->expectExceptionCode(TokenManagerException::TOKEN_NOT_VALID);

        /** @var Token $token */
        $token = $this->tokenRepository->find($token->getId());
        $this->tokenManager->validate($token->getId());
    }

    /**
     * @covers TokenManager::delete()
     * @throws Exception
     */
    public function testDeleteUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));

        $this->tokenManager->delete($token);
        $this->assertEquals(0, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));
    }

    /**
     * @covers TokenManager::delete()
     * @throws Exception
     */
    public function testDeleteTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));

        $this->tokenManager->delete($token);
        $this->assertEquals(0, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));
    }

}
