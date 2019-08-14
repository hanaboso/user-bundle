<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\ORMException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Exception\UserException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

/**
 * Class ChangePasswordCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
class ChangePasswordCommand extends PasswordCommandAbstract
{

    private const CMD_NAME = 'user:password:change';

    /**
     * @var OrmRepo|OdmRepo|ObjectRepository
     */
    private $repo;

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
     * @return int|null
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $input;
        $output->writeln('Password editing, select user by email:');
        $user = readline();
        /** @var UserInterface|null $user */
        $user = $this->repo->findOneBy(['email' => $user]);

        if (!$user) {
            $output->writeln('User with given email doesn\'t exist.');
        } else {
            $this->setPassword($output, $user);
            $output->writeln('Password changed.');
        }

        return 0;
    }

}
