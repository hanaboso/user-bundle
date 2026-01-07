<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\Messages;

use Exception;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Messages\ActivateMessage;
use Hanaboso\UserBundle\Model\Messages\UserMessageAbstract;
use Hanaboso\UserBundle\Model\MessageSubject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ActivateMessageTest
 *
 * @package UserBundleTests\Unit\Model\Messages
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ActivateMessage::class)]
#[CoversClass(UserMessageAbstract::class)]
final class ActivateMessageTest extends TestCase
{

    /**
     * @throws Exception
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
                'content'     => '',
                'dataContent' => ['link' => $tkn->getHash()],
                'from'        => '',
                'subject'     => MessageSubject::USER_ACTIVATE,
                'template'    => '',
                'to'          => 'test@example.com',
            ],
            $message->getMessage(),
        );
    }

}
