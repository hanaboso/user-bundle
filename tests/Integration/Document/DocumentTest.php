<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Document;

use Exception;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Document\UserAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class DocumentTest
 *
 * @package UserBundleTests\Integration\Document
 */
#[CoversClass(TmpUser::class)]
#[CoversClass(Token::class)]
#[CoversClass(User::class)]
#[CoversClass(UserAbstract::class)]
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
            $existingToken->getCreated()->format('d. m. Y H:i:s'),
        );

        $tokenUser     = $token->getUser();
        $eTokenUser    = $existingToken->getUser();
        $tmpTokenUser  = $token->getTmpUser();
        $eTmpTokenUser = $existingToken->getTmpUser();

        self::assertEquals($tokenUser?->getEmail(), $eTokenUser?->getEmail());
        self::assertEquals($tmpTokenUser?->getEmail(), $eTmpTokenUser?->getEmail());
    }

}
