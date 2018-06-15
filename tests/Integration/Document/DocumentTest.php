<?php declare(strict_types=1);

namespace Tests\Integration\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DocumentTest
 *
 * @package Tests\Integration\Document
 */
final class DocumentTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testReferences(): void
    {
        $tokenRepository = $this->dm->getRepository(Token::class);

        /** @var User $user */
        $user = (new User())->setEmail('email@example.com');

        /** @var TmpUser $tmpUser */
        $tmpUser = (new TmpUser())->setEmail('email@example.com');

        $this->dm->persist($user);
        $this->dm->persist($tmpUser);
        $this->dm->flush();

        $token = (new Token())
            ->setTmpUser($tmpUser)
            ->setUser($user);

        $this->dm->persist($token);
        $this->dm->flush();
        $this->dm->clear();

        /** @var Token $existingToken */
        $existingToken = $tokenRepository->find($token->getId());

        $this->assertEquals(
            $token->getCreated()->format('d. m. Y H:i:s'),
            $existingToken->getCreated()->format('d. m. Y H:i:s')
        );
        $this->assertEquals($token->getUser()->getEmail(), $existingToken->getUser()->getEmail());
        $this->assertEquals($token->getTmpUser()->getEmail(), $existingToken->getTmpUser()->getEmail());
    }

}