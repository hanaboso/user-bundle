<?php declare(strict_types=1);

namespace Tests\Integration\Repository\Document;

use DateTime;
use Exception;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Repository\Document\TokenRepository;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class TokenRepositoryTest
 *
 * @package Tests\Integration\Repository\Document
 */
final class TokenRepositoryTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testGetFreshToken(): void
    {
        $token = new Token();
        $this->persistAndFlush($token);
        $this->dm->clear();

        /** @var TokenRepository $rep */
        $rep = $this->dm->getRepository(Token::class);
        self::assertNotNull($rep->getFreshToken($token->getHash()));

        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('-2 days'));
        $this->persistAndFlush($token);
        $this->dm->clear();

        self::assertNull($rep->getFreshToken($token->getId()));
    }

}
