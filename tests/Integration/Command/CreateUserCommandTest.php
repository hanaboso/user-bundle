<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Command;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class CreateUserCommandTest
 *
 * @package UserBundleTests\Integration\Command
 *
 * @covers  \Hanaboso\UserBundle\Command\CreateUserCommand
 */
final class CreateUserCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @var CommandTester
     */
    private CommandTester $tester;

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Command\CreateUserCommand::execute
     */
    public function testExecute(): void
    {
        $user = (new User())->setEmail('another-user@example.com');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->tester->setInputs(
            ['', 'another-user@example.com', 'user@example.com', '', 'password', 'Unknown', 'password'],
        );
        $this->tester->execute([]);

        /** @var UserInterface $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => 'user@example.com']);

        self::assertEquals(
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

        $this->tester = new CommandTester((new Application(self::$kernel))->get('user:create'));
    }

}
