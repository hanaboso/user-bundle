<?php declare(strict_types=1);

namespace Tests\Unit\Model\Mailer;

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
 * @package Tests\Unit\Model\Mailer
 */
final class MailerTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testSendSync(): void
    {
        $producer = $this->createMock(Publisher::class);
        $producer
            ->expects($this->never())
            ->method('publish');

        $mailHandler = $this->createMock(MailHandler::class);
        $mailHandler
            ->expects($this->once())
            ->method('send');

        $mailer = new Mailer($producer, $mailHandler, 'from@email.com', FALSE);
        $mailer->send($this->getMessage());
    }

    /**
     * @throws Exception
     */
    public function testSendAsync(): void
    {
        $producer = $this->createMock(Publisher::class);
        $producer
            ->expects($this->once())
            ->method('publish');

        $mailHandler = $this->createMock(MailHandler::class);
        $mailHandler
            ->expects($this->never())
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
