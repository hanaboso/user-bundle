<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository as OdmRepo;
use Doctrine\ORM\EntityRepository as OrmRepo;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use Hanaboso\UserBundle\Repository\Entity\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListUsersCommand
 *
 * @package Hanaboso\UserBundle\Command
 */
class ListUsersCommand extends Command
{

    private const CMD_NAME = 'user:list';

    /**
     * @var OrmRepo<User|DmUser>|OdmRepo<User|DmUser>
     */
    private $repo;

    /**
     * ListUsersCommand constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     *
     * @throws ResourceProviderException
     */
    public function __construct(DatabaseManagerLocator $userDml, ResourceProvider $provider)
    {
        parent::__construct();

        /** @phpstan-var class-string<User|DmUser> $userClass */
        $userClass  = $provider->getResource(ResourceEnum::USER);
        $dm         = $userDml->get();
        $this->repo = $dm->getRepository($userClass);
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('List all users.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;
        /** @var UserRepository<User> $repo */
        $repo  = $this->repo;
        $table = new Table($output);
        $table
            ->setHeaders(['Email', 'Created'])
            ->setRows($repo->getArrayOfUsers());

        $table->render();

        return 0;
    }

}
