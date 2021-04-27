<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

use Hanaboso\UserBundle\Entity\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class UserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
class UserEvent extends Event
{

    public const USER_LOGIN           = 'user.login';
    public const USER_LOGOUT          = 'user.logout';
    public const USER_REGISTER        = 'user.register';
    public const USER_ACTIVATE        = 'user.activate';
    public const USER_RESET_PASSWORD  = 'user.reset.password';
    public const USER_DELETE_BEFORE   = 'user.delete.before';
    public const USER_DELETE_AFTER    = 'user.delete.after';
    public const USER_CHANGE_PASSWORD = 'user.change.password';

    /**
     * UserEvent constructor.
     *
     * @param UserInterface      $user
     * @param UserInterface|null $loggedUser
     * @param UserInterface|null $tmpUser
     */
    public function __construct(
        private UserInterface $user,
        private ?UserInterface $loggedUser = NULL,
        private ?UserInterface $tmpUser = NULL
    )
    {
    }

    /**
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @return UserInterface
     */
    public function getLoggedUser(): UserInterface
    {
        return $this->loggedUser ?? $this->user;
    }

    /**
     * @return UserInterface|null
     */
    public function getTmpUser(): ?UserInterface
    {
        return $this->tmpUser;
    }

}
