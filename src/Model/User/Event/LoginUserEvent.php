<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class LoginUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class LoginUserEvent extends UserEvent
{

    public const NAME = self::USER_LOGIN;

}
