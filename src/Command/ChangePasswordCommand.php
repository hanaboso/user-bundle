<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
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
 * Class ChangePasswordCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
class ChangePasswordCommand extends Command
{

    private const CMD_NAME = 'user:password:change';

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
     * ChangePasswordCommand constructor.
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
        $this->dm      = $userDml->get();
        $this->repo    = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
        $this->encoder = $encoderFactory->getEncoder($provider->getResource(ResourceEnum::USER));
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Changes user\'s password.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Password editing, select user by email:');

        $pwd1 = '';
        $user = readline();
        /** @var UserInterface $user */
        $user = $this->repo->findOneBy(['email' => $user]);

        if (!$user) {
            $output->writeln('User with given email doesn\'t exist.');
        } else {
            while (TRUE) {
                $output->writeln('Set new password:');
                system('stty -echo');
                $pwd1 = trim(fgets(STDIN));
                $output->writeln('Repeat password:');
                $pwd2 = trim(fgets(STDIN));
                system('stty echo');

                if ($pwd1 === $pwd2) {
                    break;
                }
                $output->writeln('Passwords don\'t match.');
            }
            $user->setPassword($this->encoder->encodePassword($pwd1, ''));
            $this->dm->flush($user);

            $output->writeln('Password changed.');
        }
    }

}