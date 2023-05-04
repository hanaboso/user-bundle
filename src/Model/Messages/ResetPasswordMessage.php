<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Messages;

use Hanaboso\UserBundle\Model\MessageSubject;

/**
 * Class ResetPasswordMessage
 *
 * @package Hanaboso\UserBundle\Model\Messages
 */
class ResetPasswordMessage extends UserMessageAbstract
{

    /**
     * @var string
     */
    protected string $subject = MessageSubject::USER_RESET_PASSWORD;

    /**
     * @var string|null
     */
    protected ?string $template = NULL;

    /**
     * @var string
     */
    protected string $host = '%s';

    /**
     * @param string $host
     *
     * @return ResetPasswordMessage
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getMessage(): array
    {
        $token = $this->user->getToken();

        $this->message['to']                  = $this->user->getEmail();
        $this->message['dataContent']['link'] = sprintf($this->host, $token ? $token->getHash() : '');
        $this->message['subject']             = $this->subject ?? '';
        $this->message['template']            = $this->template ?? '';

        return $this->message;
    }

    /**
     * @param string $subject
     *
     * @return ResetPasswordMessage
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string|null $template
     *
     * @return ResetPasswordMessage
     */
    public function setTemplate(?string $template): self
    {
        $this->template = $template;

        return $this;
    }

}
