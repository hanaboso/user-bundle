<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Mailer;

use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Handler\MailHandler;
use Hanaboso\UserBundle\Model\Messages\UserMessageAbstract;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RabbitMqBundle\Publisher\PublisherInterface;

/**
 * Class Mailer
 *
 * @package Hanaboso\UserBundle\Model\Mailer
 */
class Mailer
{

    private const DEFAULT_MAIL_BUILDER = 'generic';

    /**
     * @var MailHandler
     */
    private $mailHandler;

    /**
     * @var PublisherInterface
     */
    private $producer;

    /**
     * @var string
     */
    private $from;

    /**
     * @var bool
     */
    private $async;

    /**
     * @var string
     */
    private $builderId;

    /**
     * Mailer constructor.
     *
     * @param PublisherInterface $producer
     * @param MailHandler        $mailHandler
     * @param string             $from
     * @param bool               $async
     * @param string             $builderId
     */
    public function __construct(
        PublisherInterface $producer,
        MailHandler $mailHandler,
        string $from,
        bool $async = TRUE,
        ?string $builderId = NULL
    )
    {
        $this->mailHandler = $mailHandler;
        $this->producer    = $producer;
        $this->from        = $from;
        $this->async       = $async;
        $this->builderId   = $builderId ?? '';

        if ($this->async === FALSE && empty($this->builderId)) {
            $this->builderId = self::DEFAULT_MAIL_BUILDER;
        }
    }

    /**
     * @param UserMessageAbstract $message
     *
     * @throws MailerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function send(UserMessageAbstract $message): void
    {
        if ($this->async) {
            $this->producer->publish(json_encode($message->getMessage()));
        } else {
            $data         = $message->getMessage();
            $data['from'] = $this->from;

            $this->mailHandler->send($this->builderId, $data);
        }
    }

}