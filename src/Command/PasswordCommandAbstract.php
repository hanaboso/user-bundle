<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Hanaboso\UserBundle\Document\User as DmUser;
use Hanaboso\UserBundle\Entity\User;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Class PasswordCommandAbstract
 *
 * @package Hanaboso\UserBundle\Command
 */
abstract class PasswordCommandAbstract extends Command
{

    protected const string PASSWORD = 'password';
    protected const string EMAIL    = 'email';

    /**
     * @var DocumentManager|EntityManager
     */
    protected DocumentManager|EntityManager $dm;

    /**
     * @var PasswordHasherInterface
     */
    protected PasswordHasherInterface $encoder;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param User|DmUser     $user
     *
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function setPassword(InputInterface $input, OutputInterface $output, User|DmUser $user): void
    {
        $password = $input->getArgument(self::PASSWORD);
        if (!$password) {
            /** @var QuestionHelper $helper */
            $helper   = $this->getHelper('question');
            $password = $helper->ask(
                $input,
                $output,
                new Question('User password: ')
                    ->setValidator(
                        static function (?string $answer): string {
                            if (!$answer) {
                                throw new LogicException('Password cannot be empty!');
                            }

                            return $answer;
                        },
                    )
                    ->setHidden(TRUE),
            );

            $password = $helper->ask(
                $input,
                $output,
                new Question('User password again: ')
                    ->setValidator(
                        static function (?string $answer) use ($password): ?string {
                            if ($answer !== $password) {
                                throw new LogicException('Both passwords must be same!');
                            }

                            return $answer;
                        },
                    )
                    ->setHidden(TRUE),
            );
        }

        $user->setPassword($this->encoder->hash($password));
        $this->dm->flush();
    }

}
