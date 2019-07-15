<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class DeleteAfterUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class DeleteAfterUserEvent extends UserEvent
{

    public const NAME = self::USER_DELETE_AFTER;

}
