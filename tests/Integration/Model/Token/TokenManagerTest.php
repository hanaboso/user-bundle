<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Model\Token;

use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\ORMException;
use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\UserBundle\Model\Token\TokenManager;
use Hanaboso\UserBundle\Model\Token\TokenManagerException;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class TokenManagerTest
 *
 * @package UserBundleTests\Integration\Model\Token
 *
 * @covers  \Hanaboso\UserBundle\Model\Token\TokenManager
 */
final class TokenManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var ObjectRepository<Token>
     */
    private $tokenRepository;

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Token\TokenManager::create
     */
    public function testCreateUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        /** @var UserInterface $tokenUser */
        $tokenUser = $token->getUser();
        self::assertCount(1, $this->tokenRepository->findBy([UserTypeEnum::USER => $user]));
        self::assertEquals($user->getEmail(), $tokenUser->getEmail());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Token\TokenManager::create
     */
    public function testCreateUserTokenException(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist')->willThrowException(new ORMException());

        $manager = clone $this->tokenManager;

        $this->setProperty($manager, 'dm', $dm);

        self::expectException(TokenManagerException::class);
        $manager->create($user);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Token\TokenManager::create
     */
    public function testCreateTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($user);

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        /** @var UserInterface $tokenUser */
        $tokenUser = $token->getTmpUser();
        self::assertCount(1, $this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user]));
        self::assertEquals($user->getEmail(), $tokenUser->getEmail());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Token\TokenManager::validate
     */
    public function testValidateToken(): void
    {
        $token = new Token();
        $this->pfd($token);

        self::assertInstanceOf(Token::class, $this->tokenManager->validate($token->getHash()));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Token\TokenManager::validate
     */
    public function testValidateInvalidToken(): void
    {
        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));
        $this->pfd($token);

        $this->expectException(TokenManagerException::class);
        $this->expectExceptionCode(TokenManagerException::TOKEN_NOT_VALID);

        /** @var Token $token */
        $token = $this->tokenRepository->find($token->getId());
        $this->tokenManager->validate($token->getId());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Token\TokenManager::delete
     */
    public function testDeleteUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->pfd($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        self::assertCount(1, $this->tokenRepository->findBy([UserTypeEnum::USER => $user]));

        $this->tokenManager->delete($token);
        self::assertCount(0, $this->tokenRepository->findBy([UserTypeEnum::USER => $user]));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Token\TokenManager::delete
     */
    public function testDeleteTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->pfd($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        self::assertCount(1, $this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user]));

        $this->tokenManager->delete($token);
        self::assertCount(0, $this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user]));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenManager    = self::$container->get('hbpf.user.manager.token');
        $this->tokenRepository = $this->dm->getRepository(Token::class);
    }

}
