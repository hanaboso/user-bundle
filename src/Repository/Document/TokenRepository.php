<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class TokenRepository
 *
 * @package Hanaboso\UserBundle\Repository\Document
 *
 * @phpstan-extends DocumentRepository<Token>
 */
class TokenRepository extends DocumentRepository
{

    /**
     * @param string $hash
     *
     * @return Token|null
     * @throws DateTimeException
     */
    public function getFreshToken(string $hash): ?Token
    {
        /** @var Token $token */
        $token = $this->createQueryBuilder()
            ->field('hash')->equals($hash)
            ->field('created')->gte(DateTimeUtils::getUTCDateTime('-1 Day'))
            ->getQuery()
            ->getSingleResult();

        return $token;
    }

    /**
     * @param UserInterface $user
     *
     * @return Token[]
     */
    public function getExistingTokens(UserInterface $user): array
    {
        return $this->findBy([$user->getType() === UserTypeEnum::USER ? 'user' : 'tmpUser' => $user]);
    }

}
