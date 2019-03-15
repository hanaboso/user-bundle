<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\UserBundle\Entity\Token;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class TokenRepository
 *
 * @package Hanaboso\UserBundle\Repository\Entity
 */
class TokenRepository extends EntityRepository
{

    /**
     * @param string $hash
     *
     * @return Token|null
     * @throws NonUniqueResultException
     * @throws DateTimeException
     */
    public function getFreshToken(string $hash): ?Token
    {
        /** @var Token $token */
        $token = $this->createQueryBuilder('t')
            ->where('t.hash = :hash')
            ->andWhere('t.created > :created')
            ->setParameter('hash', $hash)
            ->setParameter('created', DateTimeUtils::getUTCDateTime('-1 day'))
            ->getQuery()
            ->getOneOrNullResult();

        return $token;
    }

    /**
     * @param UserInterface $user
     *
     * @return array
     */
    public function getExistingTokens(UserInterface $user): array
    {
        return $this->createQueryBuilder('t')
            ->join($user->getType() === UserTypeEnum::USER ? 't.user' : 't.tmpUser', 'u')
            ->where('u.id = :id')
            ->setParameter('id', $user->getId())
            ->getQuery()
            ->getResult();
    }

}
