<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Repository\Document;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\UserBundle\Document\User;

/**
 * Class UserRepository
 *
 * @package         Hanaboso\UserBundle\Repository\Document
 *
 * @phpstan-extends DocumentRepository<User>
 */
class UserRepository extends DocumentRepository
{

    /**
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getArrayOfUsers(): array
    {
        /** @var Iterator<User> $arr */
        $arr = $this->createQueryBuilder()
            ->select(['email', 'created'])
            ->field('deleted')
            ->equals(FALSE)
            ->getQuery()
            ->execute();

        $res = [];

        /** @var User $user */
        foreach ($arr->toArray() as $user) {
            $res[] = [
                'email'   => $user->getEmail(),
                'created' => $user->getCreated()->format('d-m-Y'),
            ];
        }

        return $res;
    }

    /**
     * @return int
     * @throws MongoDBException
     */
    public function getUserCount(): int
    {
        /** @var int $count */
        $count = $this->createQueryBuilder()
            ->field('deleted')
            ->equals(FALSE)
            ->count()
            ->getQuery()
            ->execute();

        return $count;
    }

}
