<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\Messages;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\RegisterMessage;
use Hanaboso\UserBundle\Model\Messages\UserMessageAbstract;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class RegisterMessageTest
 *
 * @package UserBundleTests\Unit\Model\Messages
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(RegisterMessage::class)]
#[CoversClass(UserMessageAbstract::class)]
final class RegisterMessageTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testGetMessage(): void
    {
        /** @var User|MockObject $user */
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $message = new RegisterMessage($user);
        self::assertEquals(
            [
                'content'     => '',
                'dataContent' => [],
                'from'        => '',
                'subject'     => MessageSubject::USER_REGISTER,
                'template'    => '',
                'to'          => 'test@example.com',
            ],
            $message->getMessage(),
        );
    }

}
