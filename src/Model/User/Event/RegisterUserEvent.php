<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class RegisterUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class RegisterUserEvent extends UserEvent
{

    public const string NAME = self::USER_REGISTER;

}
