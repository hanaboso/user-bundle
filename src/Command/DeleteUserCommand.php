<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeleteUserCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
class DeleteUserCommand extends Command
{

    private const CMD_NAME = 'user:delete';

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var OrmRepo|OdmRepo|ObjectRepository
     */
    private $repo;

    /**
     * DeleteUserCommand constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     *
     * @throws ResourceProviderException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        ResourceProvider $provider
    )
    {
        parent::__construct();
        $this->dm   = $userDml->get();
        $this->repo = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Delete user.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $input;
        $c = $this->repo->getUserCount();
        if ($c <= 1) {
            $output->writeln('Cannot delete when there is last one or none active users remaining.');
        } else {
            $output->writeln('Deleting user, select user email:');

            $email = readline();
            /** @var UserInterface|null $user */
            $user = $this->repo->findOneBy(['email' => $email]);

            if (!$user) {
                $output->writeln('User with given email doesn\'t exist.');
            } else {
                $this->dm->remove($user);
                $this->dm->flush();

                $output->writeln('User deleted.');
            }
        }

        return 0;
    }

}
