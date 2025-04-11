<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class ResetPasswordUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class ResetPasswordUserEvent extends UserEvent
{

    public const string NAME = self::USER_RESET_PASSWORD;

}
