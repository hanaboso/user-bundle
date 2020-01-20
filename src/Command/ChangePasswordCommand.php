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
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
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
     * @var OrmRepo|OdmRepo
     */
    private $repo;

    /**
     * ChangePasswordCommand constructor.
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
        $userClass     = $provider->getResource(ResourceEnum::USER);
        $this->dm      = $userDml->get();
        $this->repo    = $this->dm->getRepository($userClass);
        $this->encoder = $encoderFactory->getEncoder($userClass);
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
     * @return int
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;

        $helper = $this->getHelper('question');
        $user   = $helper->ask(
            $input,
            $output,
            (new Question('User email: '))
                ->setValidator(
                    function (?string $email): UserInterface {
                        /** @var UserInterface|null $email */
                        $email = $this->repo->findOneBy(['email' => $email]);

                        if (!$email) {
                            throw new LogicException('There is no user for given email!');
                        }

                        return $email;
                    }
                )
        );

        $this->setPassword($input, $output, $user);
        $output->writeln('Password changed.');

        return 0;
    }

}
