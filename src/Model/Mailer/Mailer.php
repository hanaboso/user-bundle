<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Mailer;

use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Handler\MailHandler;
use Hanaboso\UserBundle\Model\Messages\UserMessageAbstract;
use Hanaboso\Utils\String\Json;
use RabbitMqBundle\Publisher\PublisherInterface;

/**
 * Class Mailer
 *
 * @package Hanaboso\UserBundle\Model\Mailer
 */
final class Mailer
{

    private const DEFAULT_MAIL_BUILDER = 'generic';

    /**
     * @var string
     */
    private string $builderId;

    /**
     * Mailer constructor.
     *
     * @param PublisherInterface $producer
     * @param MailHandler        $mailHandler
     * @param string             $from
     * @param bool               $async
     * @param string|null        $builderId
     */
    public function __construct(
        private PublisherInterface $producer,
        private MailHandler $mailHandler,
        private string $from,
        private bool $async = TRUE,
        ?string $builderId = NULL
    )
    {
        $this->builderId = $builderId ?? '';

        if ($this->async === FALSE && empty($this->builderId)) {
            $this->builderId = self::DEFAULT_MAIL_BUILDER;
        }
    }

    /**
     * @param UserMessageAbstract $message
     *
     * @throws MailerException
     */
    public function send(UserMessageAbstract $message): void
    {
        if ($this->async) {
            $this->producer->publish(Json::encode($message->getMessage()));
        } else {
            $data         = $message->getMessage();
            $data['from'] = $this->from;

            $this->mailHandler->send($this->builderId, $data);
        }
    }

}
