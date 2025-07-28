<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Command;

use Exception;
use Hanaboso\UserBundle\Command\CreateUserCommand;
use Hanaboso\UserBundle\Document\User;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class CreateUserCommandTest
 *
 * @package UserBundleTests\Integration\Command
 */
#[CoversClass(CreateUserCommand::class)]
final class CreateUserCommandTest extends DatabaseTestCaseAbstract
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
        $user = new User()->setEmail('another-user@example.com');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->tester->setInputs(
            ['', 'another-user@example.com', 'user@example.com', '', 'password', 'Unknown', 'password'],
        );
        $this->tester->execute([]);

        /** @var User $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => 'user@example.com']);

        self::assertSame(
            'Creating user, select user email:  Email cannot be empty! 
Creating user, select user email:  User with given email already exist! 
Creating user, select user email: User password:  Password cannot be empty! 
User password: User password again:  Both passwords must be same! 
User password again: User created.
',
            $this->tester->getDisplay(),
        );
        self::assertNotEmpty($user->getPassword());
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

        $this->tester = new CommandTester(new Application(self::$kernel)->get('user:create'));
    }

}
