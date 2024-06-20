<?php declare(strict_types=1);

namespace UserBundleTests\Integration\Command;

use Exception;
use Hanaboso\UserBundle\Command\ChangePasswordCommand;
use Hanaboso\UserBundle\Command\PasswordCommandAbstract;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UserBundleTests\DatabaseTestCaseAbstract;

/**
 * Class ChangePasswordCommandTest
 *
 * @package UserBundleTests\Integration\Command
 */
#[CoversClass(ChangePasswordCommand::class)]
#[CoversClass(PasswordCommandAbstract::class)]
final class ChangePasswordCommandTest extends DatabaseTestCaseAbstract
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
        $user = (new User())->setEmail('user@example.com');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->tester->setInputs(['', 'user@example.com', '', 'password', 'Unknown', 'password']);
        $this->tester->execute([]);

        /** @var UserInterface $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => 'user@example.com']);

        self::assertEquals(
            'User email:  There is no user for given email! 
User email: User password:  Password cannot be empty! 
User password: User password again:  Both passwords must be same! 
User password again: Password changed.
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

        $this->tester = new CommandTester((new Application(self::$kernel))->get('user:password:change'));
    }

}
