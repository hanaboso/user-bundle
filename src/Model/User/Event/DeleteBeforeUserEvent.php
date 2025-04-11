<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User\Event;

/**
 * Class DeleteBeforeUserEvent
 *
 * @package Hanaboso\UserBundle\Model\User\Event
 */
final class DeleteBeforeUserEvent extends UserEvent
{

    public const string NAME = self::USER_DELETE_BEFORE;

}
