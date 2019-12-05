<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ORM\ORMException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var OrmRepo|OdmRepo
     */
    private $repo;

    /**
     * @var ResourceProvider
     */
    private $provider;

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

        /** @phpstan-var class-string<\Hanaboso\UserBundle\Entity\User|\Hanaboso\UserBundle\Document\User> $userClass */
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
     * @return int|null
     * @throws ResourceProviderException
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $input;
        $output->writeln('Creating user, select user email:');

        $email = readline();
        /** @var UserInterface|null $user */
        $user = $this->repo->findOneBy(['email' => $email]);

        if ($user) {
            $output->writeln('User with given email exist.');
        } else {
            $userNamespace = $this->provider->getResource(ResourceEnum::USER);

            $user = new $userNamespace();
            $user->setEmail($email);
            $this->dm->persist($user);
            $this->setPassword($output, $user);

            $output->writeln('User created.');
        }

        return 0;
    }

}
