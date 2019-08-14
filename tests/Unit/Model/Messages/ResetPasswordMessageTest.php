<?php declare(strict_types=1);

namespace Tests\Unit\Model\Messages;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ResetPasswordMessageTest
 *
 * @package Tests\Unit\Model\Messages
 */
final class ResetPasswordMessageTest extends TestCase
{

    /**
     * @covers ResetPasswordMessage::getMessage()
     * @throws Exception
     */
    public function testGetMessage(): void
    {
        /** @var User|MockObject $user */
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getUsername')->willReturn('FooTooBoo');

        $message = new ResetPasswordMessage($user);
        $message->setHost('/user/%s/set_password');
        $this->assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_RESET_PASSWORD,
                'content'     => '',
                'dataContent' => ['link' => '/user//set_password'],
                'template'    => NULL,
                'from'        => '',
            ], $message->getMessage()
        );
    }

}
