<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Model\Mailer;

use EmailServiceBundle\Handler\MailHandler;
use Exception;
use Hanaboso\UserBundle\Entity\Token;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Messages\ActivateMessage;
use PHPUnit\Framework\TestCase;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class MailerTest
 *
 * @package UserBundleTests\Unit\Model\Mailer
 *
 * @covers  \Hanaboso\UserBundle\Model\Mailer\Mailer
 */
final class MailerTest extends TestCase
{

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Mailer\Mailer::send
     */
    public function testSendSync(): void
    {
        $producer = $this->createMock(Publisher::class);
        $producer
            ->expects(self::never())
            ->method('publish');

        $mailHandler = $this->createMock(MailHandler::class);
        $mailHandler
            ->expects(self::once())
            ->method('send');

        $mailer = new Mailer($producer, $mailHandler, 'from@email.com', FALSE);
        $mailer->send($this->getMessage());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Model\Mailer\Mailer::send
     */
    public function testSendAsync(): void
    {
        $producer = $this->createMock(Publisher::class);
        $producer
            ->expects(self::once())
            ->method('publish');

        $mailHandler = $this->createMock(MailHandler::class);
        $mailHandler
            ->expects(self::never())
            ->method('send');

        $mailer = new Mailer($producer, $mailHandler, 'from@email.com');
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
