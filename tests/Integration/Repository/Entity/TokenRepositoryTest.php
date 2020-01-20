<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Repository\Entity;

use DateTime;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\UserBundle\Entity\Token;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Repository\Entity\TokenRepository;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class TokenRepositoryTest
 *
 * @package UserBundleTests\Integration\Repository\Entity
 *
 * @covers  \Hanaboso\UserBundle\Repository\Entity\TokenRepository
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
     * @covers \Hanaboso\UserBundle\Repository\Entity\TokenRepository::getFreshToken
     */
    public function testGetFreshToken(): void
    {
        $token = new Token();
        $this->pfe($token);
        $this->em->clear();

        self::assertNotNull($this->repository->getFreshToken($token->getHash()));

        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('-2 days'));
        $this->pfe($token);
        $this->em->clear();
        $this->em->clear();

        self::assertNull($this->repository->getFreshToken($token->getHash()));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Repository\Entity\TokenRepository::getExistingTokens
     */
    public function testGetExistingTokens(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $this->pfe($user);

        $token = (new Token())->setUser($user);
        $user->setToken($token);
        $this->pfe($token);

        $this->em->clear();

        self::assertCount(1, $this->repository->getExistingTokens($user));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var TokenRepository $repository */
        $repository = $this->em->getRepository(Token::class);

        $this->repository = $repository;
    }

}
