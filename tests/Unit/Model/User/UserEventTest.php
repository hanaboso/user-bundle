<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\User;

use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\User\Event\UserEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class UserEventTest
 *
 * @package UserBundleTests\Unit\Model\User
 */
#[CoversClass(UserEvent::class)]
final class UserEventTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testEvent(): void
    {
        $event = new UserEvent(
            new User()->setEmail('user@example.com'),
            new User()->setEmail('logger-user@example.com'),
            new TmpUser()->setEmail('tmp-user@example.com'),
        );

        /** @var TmpUser $tmpUser */
        $tmpUser = $event->getTmpUser();

        self::assertSame('user@example.com', $event->getUser()->getEmail());
        self::assertSame('logger-user@example.com', $event->getLoggedUser()->getEmail());
        self::assertSame('tmp-user@example.com', $tmpUser->getEmail());
    }

}
