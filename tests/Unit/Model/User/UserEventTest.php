<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\User;

use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Model\User\Event\UserEvent;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class UserEventTest
 *
 * @package UserBundleTests\Unit\Model\User
 *
 * @covers  \Hanaboso\UserBundle\Model\User\Event\UserEvent
 */
final class UserEventTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testEvent(): void
    {
        $event = new UserEvent(
            (new User())->setEmail('user@example.com'),
            (new User())->setEmail('logger-user@example.com'),
            (new TmpUser())->setEmail('tmp-user@example.com')
        );

        /** @var UserInterface $tmpUser */
        $tmpUser = $event->getTmpUser();

        self::assertEquals('user@example.com', $event->getUser()->getEmail());
        self::assertEquals('logger-user@example.com', $event->getLoggedUser()->getEmail());
        self::assertEquals('tmp-user@example.com', $tmpUser->getEmail());
    }

}
