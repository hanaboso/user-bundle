<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Repository\Entity;

use Exception;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Repository\Entity\UserRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class UserRepositoryTest
 *
 * @package UserBundleTests\Integration\Repository\Entity
 */
#[CoversClass(UserRepository::class)]
final class UserRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetArrayOfUsers(): void
    {
        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');

        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $user
                ->setPassword('pwd')
                ->setEmail(sprintf('user%s', $i));
            $em->persist($user);
            $em->flush();
        }
        $em->clear();

        /** @var UserRepository<User> $rep */
        $rep   = $em->getRepository(User::class);
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
        $em   = self::getContainer()->get('doctrine.orm.default_entity_manager');
        $user = new User();
        $user
            ->setPassword('pwd')
            ->setEmail('eml');
        $em->persist($user);
        $em->flush();

        /** @var UserRepository<User> $rep */
        $rep = $em->getRepository(User::class);

        self::assertGreaterThanOrEqual(1, $rep->getUserCount());
    }

}
