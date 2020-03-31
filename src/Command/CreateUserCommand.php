<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository as OdmRepo;
use Doctrine\ORM\EntityRepository as OrmRepo;
use Doctrine\ORM\ORMException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

/**
 * Class CreateUserCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
class CreateUserCommand extends PasswordCommandAbstract
{

    private const CMD_NAME = 'user:create';

    /**
     * @var OrmRepo<User|DmUser>|OdmRepo<User|DmUser>
     */
    private $repo;

    /**
     * @var ResourceProvider
     */
    private ResourceProvider $provider;

    /**
     * CreateUserCommand constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     * @param EncoderFactory         $encoderFactory
     *
     * @throws ResourceProviderException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        ResourceProvider $provider,
        EncoderFactory $encoderFactory
    )
    {
        parent::__construct();

        /** @phpstan-var class-string<User|DmUser> $userClass */
        $userClass      = $provider->getResource(ResourceEnum::USER);
        $this->dm       = $userDml->get();
        $this->repo     = $this->dm->getRepository($userClass);
        $this->encoder  = $encoderFactory->getEncoder($userClass);
        $this->provider = $provider;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Create new user password.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ResourceProviderException
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;

        $helper = $this->getHelper('question');
        $email  = $helper->ask(
            $input,
            $output,
            (new Question('Creating user, select user email: '))
                ->setValidator(
                    function (?string $email): string {
                        /** @var UserInterface|null $user */
                        $user = $this->repo->findOneBy(['email' => $email]);

                        if (!$email) {
                            throw new LogicException('Email cannot be empty!');
                        }

                        if ($user) {
                            throw new LogicException('User with given email already exist!');
                        }

                        return $email;
                    }
                )
        );

        $userNamespace = $this->provider->getResource(ResourceEnum::USER);

        /** @var UserInterface $user */
        $user = new $userNamespace();
        $user->setEmail($email);
        $this->dm->persist($user);
        $this->setPassword($input, $output, $user);

        $output->writeln('User created.');

        return 0;
    }

}
