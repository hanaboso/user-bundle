<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Command;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\Date\DateTimeUtils;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class ListUsersCommandTest
 *
 * @package UserBundleTests\Integration\Command
 *
 * @covers  \Hanaboso\UserBundle\Command\ListUsersCommand
 */
final class ListUsersCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @var CommandTester
     */
    private CommandTester $tester;

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Command\ListUsersCommand::execute
     */
    public function testExecute(): void
    {
        $user = (new User())->setEmail('user@example.com');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->tester->execute([]);

        self::assertEquals(
            sprintf(
                '+------------+------------------+
| Created    | Email            |
+------------+------------------+
| 04-05-2023 | user@example.com |
+------------+------------------+
',
                DateTimeUtils::getUtcDateTime()->format('d-m-Y'),
            ),
            $this->tester->getDisplay(),
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tester = new CommandTester((new Application(self::$kernel))->get('user:list'));
    }

}
