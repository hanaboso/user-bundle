<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Hanaboso\UserBundle\Entity\UserInterface;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class PasswordCommandAbstract
 *
 * @package Hanaboso\UserBundle\Command
 */
abstract class PasswordCommandAbstract extends Command
{

    /**
     * @var DocumentManager|EntityManager
     */
    protected $dm;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param UserInterface   $user
     *
     * @throws ORMException
     * @throws MongoDBException
     */
    protected function setPassword(InputInterface $input, OutputInterface $output, UserInterface $user): void
    {
        $helper   = $this->getHelper('question');
        $password = $helper->ask(
            $input,
            $output,
            (new Question('User password: '))
                ->setValidator(
                    static function (?string $answer): string {
                        if (!$answer) {
                            throw new LogicException('Password cannot be empty!');
                        }

                        return $answer;
                    }
                )
                ->setHidden(TRUE)
        );

        $password = $helper->ask(
            $input,
            $output,
            (new Question('User password again: '))
                ->setValidator(
                    static function (?string $answer) use ($password): ?string {
                        if ($answer !== $password) {
                            throw new LogicException('Both passwords must be same!');
                        }

                        return $answer;
                    }
                )
                ->setHidden(TRUE)
        );

        $user->setPassword($this->encoder->encodePassword($password, ''));
        $this->dm->flush();
    }

}
