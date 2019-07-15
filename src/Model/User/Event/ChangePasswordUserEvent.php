<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class ChangePasswordUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class ChangePasswordUserEvent extends UserEvent
{

    public const NAME = self::USER_CHANGE_PASSWORD;

}
