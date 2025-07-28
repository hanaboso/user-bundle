<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository as OrmRepo;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\UserBundle\Repository\Entity\UserRepository;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class DeleteUserCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
final class DeleteUserCommand extends Command
{

    private const string CMD_NAME = 'user:delete';

    /**
     * @var DocumentManager|EntityManager
     */
    private DocumentManager|EntityManager $dm;

    /**
     * @var OrmRepo<User>
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
    public function __construct(DatabaseManagerLocator $userDml, ResourceProvider $provider)
    {
        parent::__construct();

        /** @phpstan-var class-string<User> $userClass */
        $userClass  = $provider->getResource(ResourceEnum::USER);
        $this->dm   = $userDml->get();
        $this->repo = $this->dm->getRepository($userClass);
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
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var UserRepository<User> $repo */
        $repo = $this->repo;
        if ($repo->getUserCount() <= 1) {
            $output->writeln('Cannot delete when there is last one or none active users remaining.');
        } else {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $user   = $helper->ask(
                $input,
                $output,
                new Question('Deleting user, select user email: ')
                    ->setValidator(
                        function (?string $email): User|DmUser {
                            if (!$email) {
                                throw new LogicException('Email cannot be empty!');
                            }

                            /** @var User|DmUser|null $user */
                            $user = $this->repo->findOneBy(['email' => $email]);

                            if (!$user) {
                                throw new LogicException('User with given email already exist!');
                            }

                            return $user;
                        },
                    ),
            );

            $this->dm->remove($user);
            $this->dm->flush();

            $output->writeln('User deleted.');
        }

        return 0;
    }

}
