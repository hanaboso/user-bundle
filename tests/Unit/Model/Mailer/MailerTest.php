<?php declare(strict_types=1);

namespace Tests\Unit\Model\Mailer;

use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Handler\MailHandler;
use Hanaboso\UserBundle\Entity\Token;
use Hanaboso\UserBundle\Entity\User;
use Hanaboso\UserBundle\Model\Mailer\Mailer;
use Hanaboso\UserBundle\Model\Messages\ActivateMessage;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class MailerTest
 *
 * @package Tests\Unit\Model\Mailer
 */
final class MailerTest extends TestCase
{

    /**
     * @throws MailerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @throws ContainerExceptionInterface
     * @throws MailerException
     * @throws NotFoundExceptionInterface
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