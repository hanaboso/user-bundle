<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Document;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class TokenRepository
 *
 * @package         Hanaboso\UserBundle\Repository\Document
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
            ->field('created')->gte(DateTimeUtils::getUtcDateTime('-1 Day'))
            ->getQuery()
            ->getSingleResult();

        return $token;
    }

    /**
     * @param User|TmpUser $user
     *
     * @return Token[]
     */
    public function getExistingTokens(User|TmpUser $user): array
    {
        return $this->findBy([$user->getType() === UserTypeEnum::USER ? 'user' : 'tmpUser' => $user]);
    }

}
