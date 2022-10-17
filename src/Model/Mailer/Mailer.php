<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Mailer;

use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Handler\MailHandler;
use Hanaboso\UserBundle\Model\Messages\UserMessageAbstract;

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
     * @param MailHandler $mailHandler
     * @param string      $from
     * @param string|null $builderId
     */
    public function __construct(private MailHandler $mailHandler, private string $from, ?string $builderId = NULL)
    {
        $this->builderId = $builderId ?? '';

        if (empty($this->builderId)) {
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
        $data         = $message->getMessage();
        $data['from'] = $this->from;

        $this->mailHandler->send($this->builderId, $data);
    }

}
