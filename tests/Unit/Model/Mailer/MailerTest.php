<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\Mailer;

use EmailServiceBundle\Handler\MailHandler;
use Exception;
use Hanaboso\UserBundle\Entity\Token;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Messages\ActivateMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class MailerTest
 *
 * @package UserBundleTests\Unit\Model\Mailer
 *
 * @covers  \Hanaboso\UserBundle\Model\Mailer\Mailer
 */
#[CoversClass(Mailer::class)]
final class MailerTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testSendSync(): void
    {
        $mailHandler = $this->createMock(MailHandler::class);
        $mailHandler
            ->expects(self::once())
            ->method('send');

        $mailer = new Mailer($mailHandler, 'from@email.com');
        $mailer->send($this->getMessage());
    }

    /**
     * @return ActivateMessage
     * @throws Exception
     */
    private function getMessage(): ActivateMessage
    {
        $user = new User();
        $user
            ->setToken(new Token())
            ->setEmail('user@email.com');

        $message = new ActivateMessage($user);
        $message->setHost('abc');

        return $message;
    }

}
