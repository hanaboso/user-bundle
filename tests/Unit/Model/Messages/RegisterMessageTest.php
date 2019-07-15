<?php declare(strict_types=1);

namespace Tests\Unit\Model\Messages;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\RegisterMessage;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class RegisterMessageTest
 *
 * @package Tests\Unit\Model\Messages
 */
final class RegisterMessageTest extends TestCase
{

    /**
     * @covers RegisterMessage::getMessage()
     * @throws Exception
     */
    public function testGetMessage(): void
    {
        /** @var User|MockObject $user */
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $message = new RegisterMessage($user);
        $this->assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_REGISTER,
                'content'     => '',
                'dataContent' => [],
                'template'    => '',
                'from'        => '',
            ], $message->getMessage()
        );
    }

}
