<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\UserBundle\Document\User;

/**
 * Class UserRepository
 *
 * @package Hanaboso\UserBundle\Repository\Document
 */
class UserRepository extends DocumentRepository
{

    /**
     * @return array
     */
    public function getArrayOfUsers(): array
    {
        $arr = $this->createQueryBuilder()
            ->select(['email', 'created'])
            ->field('deleted')
            ->equals(FALSE)
            ->getQuery()
            ->execute()
            ->toArray();

        $res = [];

        /** @var User $user */
        foreach ($arr as $user) {
            $res[] = [
                'email'   => $user->getEmail(),
                'created' => $user->getCreated()->format('d-m-Y'),
            ];
        }

        return $res;
    }

    /**
     * @return int
     */
    public function getUserCount(): int
    {
        return $this->createQueryBuilder()
            ->field('deleted')
            ->equals(FALSE)
            ->count()
            ->getQuery()
            ->execute();
    }

}