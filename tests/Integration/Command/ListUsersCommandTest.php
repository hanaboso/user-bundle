<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Command;

use Exception;
use Hanaboso\UserBundle\Command\ListUsersCommand;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class ListUsersCommandTest
 *
 * @package UserBundleTests\Integration\Command
 */
#[CoversClass(ListUsersCommand::class)]
final class ListUsersCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @var CommandTester
     */
    private CommandTester $tester;

    /**
     * @throws Exception
     */
    public function testExecute(): void
    {
        $user = new User()->setEmail('user@example.com');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->tester->execute([]);

        self::assertEquals(
            sprintf(
                '+------------+------------------+
| Created    | Email            |
+------------+------------------+
| %s | user@example.com |
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

        if(self::$kernel === NULL){
            self::fail();
        }

        $this->tester = new CommandTester(new Application(self::$kernel)->get('user:list'));
    }

}
