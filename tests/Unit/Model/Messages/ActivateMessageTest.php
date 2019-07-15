<?php declare(strict_types=1);

namespace Tests\Unit\Model\Messages;

use Exception;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\ActivateMessage;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ActivateMessageTest
 *
 * @package Tests\Unit\Model\Messages
 */
final class ActivateMessageTest extends TestCase
{

    /**
     * @covers ActivateMessage::getMessage()
     * @throws Exception
     */
    public function testGetMessage(): void
    {
        $tkn = new Token();

        /** @var User|MockObject $user */
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getToken')->willReturn($tkn);
        $message = new ActivateMessage($user);
        $this->assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_ACTIVATE,
                'content'     => '',
                'dataContent' => ['link' => $tkn->getHash()],
                'template'    => '',
                'from'        => '',
            ], $message->getMessage()
        );
    }

}
