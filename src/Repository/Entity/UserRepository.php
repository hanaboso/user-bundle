<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Entity;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class UserRepository
 *
 * @package Hanaboso\UserBundle\Repository\Entity
 */
class UserRepository extends EntityRepository
{

    /**
     * @return array
     */
    public function getArrayOfUsers(): array
    {
        $arr = $this->createQueryBuilder('u')
            ->select(['u.email', 'u.created'])
            ->where('u.deleted = 0')
            ->getQuery()
            ->getArrayResult();

        foreach ($arr as $index => $row) {
            /** @var DateTime $created */
            $created                = $row['created'];
            $arr[$index]['created'] = $created->format('d-m-Y');
        }

        return $arr;
    }

    /**
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getUserCount(): int
    {
        $res = $this->createQueryBuilder('u')
            ->select('count(u.email) as amount')
            ->where('u.deleted = 0')
            ->getQuery()
            ->getSingleResult();

        return intval($res['amount']);
    }

}