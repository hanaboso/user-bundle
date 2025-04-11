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

    public const string USER_LOGIN           = 'user.login';
    public const string USER_LOGOUT          = 'user.logout';
    public const string USER_REGISTER        = 'user.register';
    public const string USER_ACTIVATE        = 'user.activate';
    public const string USER_RESET_PASSWORD  = 'user.reset.password';
    public const string USER_DELETE_BEFORE   = 'user.delete.before';
    public const string USER_DELETE_AFTER    = 'user.delete.after';
    public const string USER_CHANGE_PASSWORD = 'user.change.password';

    /**
     * UserEvent constructor.
     *
     * @param UserInterface      $user
     * @param UserInterface|null $loggedUser
     * @param UserInterface|null $tmpUser
     */
    public function __construct(
        private readonly UserInterface $user,
        private readonly ?UserInterface $loggedUser = NULL,
        private readonly ?UserInterface $tmpUser = NULL,
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
