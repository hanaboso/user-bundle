<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\Messages;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ResetPasswordMessageTest
 *
 * @package UserBundleTests\Unit\Model\Messages
 *
 * @covers  \Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage
 * @covers  \Hanaboso\UserBundle\Model\Messages\UserMessageAbstract
 */
final class ResetPasswordMessageTest extends TestCase
{

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage::getMessage
     */
    public function testGetMessage(): void
    {
        /** @var User|MockObject $user */
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getUsername')->willReturn('FooTooBoo');

        $message = (new ResetPasswordMessage($user))
            ->setHost('/user/%s/set_password')
            ->setSubject('User reset password')
            ->setTemplate('')
            ->setFrom('');
        self::assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_RESET_PASSWORD,
                'content'     => '',
                'dataContent' => ['link' => '/user//set_password'],
                'template'    => NULL,
                'from'        => '',
            ],
            $message->getMessage(),
        );
    }

}
