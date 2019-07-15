<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class LogoutUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class LogoutUserEvent extends UserEvent
{

    public const NAME = self::USER_LOGOUT;

}
