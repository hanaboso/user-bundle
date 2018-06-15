<?php declare(strict_types=1);

namespace Tests\Integration\Repository\Entity;

use DateTime;
use Exception;
use Hanaboso\UserBundle\Entity\Token;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class TokenRepositoryTest
 *
 * @package Tests\Integration\Repository\Entity
 */
final class TokenRepositoryTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testGetFreshToken(): void
    {
        $em = $this->c->get('doctrine.orm.default_entity_manager');

        $token = new Token();
        $em->persist($token);
        $em->flush($token);
        $em->clear();

        self::assertNotNull($em->getRepository(Token::class)->getFreshToken($token->getHash()));

        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('-2 days'));
        $em->persist($token);
        $em->flush($token);
        $em->clear();

        self::assertNull($em->getRepository(Token::class)->getFreshToken($token->getHash()));
    }

}