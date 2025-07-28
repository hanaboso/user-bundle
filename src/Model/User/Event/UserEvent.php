<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

use Hanaboso\UserBundle\Document\UserAbstract as DocumentUserAbstract;
use Hanaboso\UserBundle\Entity\UserAbstract;
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
     * @param UserAbstract|DocumentUserAbstract      $user
     * @param UserAbstract|DocumentUserAbstract|null $loggedUser
     * @param UserAbstract|DocumentUserAbstract|null $tmpUser
     */
    public function __construct(
        private readonly UserAbstract|DocumentUserAbstract $user,
        private readonly UserAbstract|DocumentUserAbstract|null $loggedUser = NULL,
        private readonly UserAbstract|DocumentUserAbstract|null $tmpUser = NULL,
    )
    {
    }

    /**
     * @return UserAbstract|DocumentUserAbstract
     */
    public function getUser(): UserAbstract|DocumentUserAbstract
    {
        return $this->user;
    }

    /**
     * @return UserAbstract|DocumentUserAbstract
     */
    public function getLoggedUser(): UserAbstract|DocumentUserAbstract
    {
        return $this->loggedUser ?? $this->user;
    }

    /**
     * @return UserAbstract|DocumentUserAbstract|null
     */
    public function getTmpUser(): UserAbstract|DocumentUserAbstract|null
    {
        return $this->tmpUser;
    }

}
