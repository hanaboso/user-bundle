<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class DocumentTest
 *
 * @package UserBundleTests\Integration\Document
 *
 * @covers  \Hanaboso\UserBundle\Document\TmpUser
 * @covers  \Hanaboso\UserBundle\Document\Token
 * @covers  \Hanaboso\UserBundle\Document\User
 * @covers  \Hanaboso\UserBundle\Document\UserAbstract
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

        self::assertEquals(
            $token->getCreated()->format('d. m. Y H:i:s'),
            $existingToken->getCreated()->format('d. m. Y H:i:s')
        );
        /** @var UserInterface $tokenUser */
        $tokenUser = $token->getUser();
        /** @var UserInterface $eTokenUser */
        $eTokenUser = $existingToken->getUser();
        /** @var UserInterface $tmpTokenUser */
        $tmpTokenUser = $token->getTmpUser();
        /** @var UserInterface $eTmpTokenUser */
        $eTmpTokenUser = $existingToken->getTmpUser();

        self::assertEquals($tokenUser->getEmail(), $eTokenUser->getEmail());
        self::assertEquals($tmpTokenUser->getEmail(), $eTmpTokenUser->getEmail());
    }

}
