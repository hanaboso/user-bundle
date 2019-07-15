<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Exception\UserException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\UserBundle\Repository\Entity\UserRepository as OrmRepo;
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
     * @var OrmRepo|OdmRepo|ObjectRepository
     */
    private $repo;

    /**
     * ListUsersCommand constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     *
     * @throws UserException
     */
    public function __construct(
        DatabaseManagerLocator $userDml,
        ResourceProvider $provider
    )
    {
        parent::__construct();
        $dm         = $userDml->get();
        $this->repo = $dm->getRepository($provider->getResource(ResourceEnum::USER));
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
     * @return int|null
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $input;
        $table = new Table($output);
        $table
            ->setHeaders(['Email', 'Created'])
            ->setRows($this->repo->getArrayOfUsers());

        $table->render();

        return 0;
    }

}
