<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\Messages;

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
 * @package UserBundleTests\Unit\Model\Messages
 *
 * @covers  \Hanaboso\UserBundle\Model\Messages\ActivateMessage
 * @covers  \Hanaboso\UserBundle\Model\Messages\UserMessageAbstract
 */
final class ActivateMessageTest extends TestCase
{

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Messages\ActivateMessage::getMessage
     */
    public function testGetMessage(): void
    {
        $tkn = new Token();

        /** @var User|MockObject $user */
        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getToken')->willReturn($tkn);
        $message = (new ActivateMessage($user))
            ->setHost($tkn->getHash())
            ->setSubject('Activate user account')
            ->setTemplate('')
            ->setFrom('');
        self::assertEquals(
            [
                'to'          => 'test@example.com',
                'subject'     => MessageSubject::USER_ACTIVATE,
                'content'     => '',
                'dataContent' => ['link' => $tkn->getHash()],
                'template'    => '',
                'from'        => '',
            ],
            $message->getMessage(),
        );
    }

}
