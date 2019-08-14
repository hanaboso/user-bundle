<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Hanaboso\UserBundle\Entity\UserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class AbstractPasswordCommand
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
     * @param OutputInterface $output
     * @param UserInterface   $user
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function setPassword(OutputInterface $output, UserInterface $user): void
    {
        $pwd1 = '';
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
    }

}
