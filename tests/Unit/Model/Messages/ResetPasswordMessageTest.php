<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\Messages;

use Exception;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\ResetPasswordMessage;
use Hanaboso\UserBundle\Model\Messages\UserMessageAbstract;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
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
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ResetPasswordMessage::class)]
#[CoversClass(UserMessageAbstract::class)]
final class ResetPasswordMessageTest extends TestCase
{

    /**
     * @throws Exception
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
                'content'     => '',
                'dataContent' => ['link' => '/user//set_password'],
                'from'        => '',
                'subject'     => MessageSubject::USER_RESET_PASSWORD,
                'template'    => NULL,
                'to'          => 'test@example.com',
            ],
            $message->getMessage(),
        );
    }

}
