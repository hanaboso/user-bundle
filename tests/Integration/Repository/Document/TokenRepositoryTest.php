<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Repository\Document;

use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Exception;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Repository\Document\TokenRepository;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class TokenRepositoryTest
 *
 * @package UserBundleTests\Integration\Repository\Document
 *
 * @covers  \Hanaboso\UserBundle\Repository\Document\TokenRepository
 */
final class TokenRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ObjectRepository<Token>&TokenRepository
     */
    private TokenRepository $repository;

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Repository\Document\TokenRepository::getFreshToken
     */
    public function testGetFreshToken(): void
    {
        $token = new Token();
        $this->pfd($token);
        $this->dm->clear();

        /** @var TokenRepository $rep */
        $rep = $this->dm->getRepository(Token::class);
        self::assertNotNull($rep->getFreshToken($token->getHash()));

        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('-2 days'));
        $this->pfd($token);
        $this->dm->clear();

        self::assertNull($rep->getFreshToken($token->getId()));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Repository\Document\TokenRepository::getExistingTokens
     */
    public function testGetExistingTokens(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $this->pfd($user);

        $token = (new Token())->setUser($user);
        $user->setToken($token);
        $this->pfd($token);

        self::assertCount(1, $this->repository->getExistingTokens($user));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var TokenRepository $repository */
        $repository = $this->dm->getRepository(Token::class);

        $this->repository = $repository;
    }

}
