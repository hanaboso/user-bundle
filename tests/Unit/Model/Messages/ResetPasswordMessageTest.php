<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: sep
 * Date: 17.9.17
 * Time: 14:47
 */

namespace Tests\Unit\Model\Messages;

use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\TestCase;

/**
 * Class ResetPasswordMessageTest
 *
 * @package Tests\Unit\Model\Messages
 */
class ResetPasswordMessageTest extends TestCase
{

    /**
     * @covers ResetPasswordMessage::getMessage()
     */
    public function testGetMessage(): void
    {
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getUsername')->willReturn('FooTooBoo');

        $message = new ResetPasswordMessage($user);
        $this->assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_RESET_PASSWORD,
                'content'     => '',
                'dataContent' => ['username' => 'FooTooBoo'],
                'template'    => NULL,
                'from'        => '',
            ], $message->getMessage()
        );
    }

}
