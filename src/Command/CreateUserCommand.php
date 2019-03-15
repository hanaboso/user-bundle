<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Exception\UserException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class CreateUserCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
class CreateUserCommand extends Command
{

    private const CMD_NAME = 'user:create';

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var OrmRepo|OdmRepo|ObjectRepository
     */
    private $repo;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

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
     * @throws UserException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        ResourceProvider $provider,
        EncoderFactory $encoderFactory
    )
    {
        parent::__construct();
        $this->dm       = $userDml->get();
        $this->repo     = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
        $this->encoder  = $encoderFactory->getEncoder($provider->getResource(ResourceEnum::USER));
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
     * @throws UserException
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $input;
        $output->writeln('Creating user, select user email:');

        $pwd1  = '';
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
            while (TRUE) {
                $output->writeln('Set new password:');
                system('stty -echo');
                $pwd1 = trim((string) fgets(STDIN));
                $output->writeln('Repeat password:');
                $pwd2 = trim((string) fgets(STDIN));
                system('stty echo');

                if ($pwd1 === $pwd2) {
                    break;
                }
                $output->writeln('Passwords don\'t match.');
            }
            $user->setPassword($this->encoder->encodePassword($pwd1, ''));
            $this->dm->flush($user);

            $output->writeln('User created.');
        }

        return 0;
    }

}
