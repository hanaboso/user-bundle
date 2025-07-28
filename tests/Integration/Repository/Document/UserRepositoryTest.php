<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Repository\Document;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Repository\Document\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class UserRepositoryTest
 *
 * @package UserBundleTests\Integration\Repository\Document
 */
#[CoversClass(UserRepository::class)]
final class UserRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetArrayOfUsers(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $user
                ->setPassword('pwd')
                ->setEmail(sprintf('user%s', $i));
            $this->pfd($user);
        }
        $this->dm->clear();

        /** @var UserRepository $rep */
        $rep   = $this->dm->getRepository(User::class);
        $users = $rep->getArrayOfUsers();

        self::assertGreaterThanOrEqual(2, count($users));
        self::assertArrayHasKey('email', $users[0]);
        self::assertArrayHasKey('created', $users[0]);
    }

    /**
     * @throws Exception
     */
    public function testGetUserCount(): void
    {
        $user = new User();
        $user
            ->setPassword('pwd')
            ->setEmail('eml');
        $this->pfd($user);
        $user = new User();
        $user
            ->setPassword('pwd')
            ->setEmail('eml2');
        $this->pfd($user);

        /** @var UserRepository $rep */
        $rep = $this->dm->getRepository(User::class);

        self::assertGreaterThanOrEqual(1, $rep->getUserCount());
    }

}
