<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Command;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class DeleteUserCommandTest
 *
 * @package UserBundleTests\Integration\Command
 *
 * @covers  \Hanaboso\UserBundle\Command\DeleteUserCommand
 */
final class DeleteUserCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @var CommandTester
     */
    private CommandTester $tester;

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Command\DeleteUserCommand::execute
     */
    public function testExecute(): void
    {
        $user        = (new User())->setEmail('user@example.com');
        $anotherUser = (new User())->setEmail('another-user@example.com');

        $this->dm->persist($user);
        $this->dm->persist($anotherUser);
        $this->dm->flush();
        $this->dm->clear();

        $this->tester->setInputs(['', 'Unknown', 'user@example.com', '']);
        $this->tester->execute([]);

        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => 'user@example.com']);

        self::assertEquals(
            'Deleting user, select user email:  Email cannot be empty! 
Deleting user, select user email:  User with given email already exist! 
Deleting user, select user email: User deleted.
',
            $this->tester->getDisplay(),
        );
        self::assertNull($user);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Command\DeleteUserCommand::execute
     */
    public function testExecuteException(): void
    {
        $this->tester->execute([]);

        self::assertEquals(
            'Cannot delete when there is last one or none active users remaining.
',
            $this->tester->getDisplay(),
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tester = new CommandTester((new Application(self::$kernel))->get('user:delete'));
    }

}
