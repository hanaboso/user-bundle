<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ORM\EntityRepository as OrmRepo;
use Doctrine\ORM\Exception\ORMException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use LogicException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

/**
 * Class ChangePasswordCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
final class ChangePasswordCommand extends PasswordCommandAbstract
{

    private const string CMD_NAME = 'user:password:change';

    /**
     * @var OrmRepo<User>
     */
    private $repo;

    /**
     * ChangePasswordCommand constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     * @param PasswordHasherFactory  $encoderFactory
     *
     * @throws ResourceProviderException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        ResourceProvider $provider,
        PasswordHasherFactory $encoderFactory,
    )
    {
        parent::__construct();

        /** @phpstan-var class-string<User> $userClass */
        $userClass     = $provider->getResource(ResourceEnum::USER);
        $this->dm      = $userDml->get();
        $this->repo    = $this->dm->getRepository($userClass);
        $this->encoder = $encoderFactory->getPasswordHasher($userClass);
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Changes user\'s password.')
            ->addArgument(self::EMAIL, InputArgument::OPTIONAL)
            ->addArgument(self::PASSWORD, InputArgument::OPTIONAL);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $input->getArgument(self::EMAIL);
        if (!$user) {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $user   = $helper->ask(
                $input,
                $output,
                new Question('User email: ')
                    ->setValidator(
                        function (?string $email): User|DmUser {
                            /** @var User|DmUser|null $email */
                            $email = $this->repo->findOneBy(['email' => $email]);

                            if (!$email) {
                                throw new LogicException('There is no user for given email!');
                            }

                            return $email;
                        },
                    ),
            );
        }

        $this->setPassword($input, $output, $user);
        $output->writeln('Password changed.');

        return 0;
    }

}
