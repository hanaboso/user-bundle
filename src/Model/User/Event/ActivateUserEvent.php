<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class ActivateUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class ActivateUserEvent extends UserEvent
{

    public const string NAME = self::USER_ACTIVATE;

}
