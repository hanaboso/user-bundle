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
    protected $subject = MessageSubject::USER_RESET_PASSWORD;

    /**
     * @var string|null
     */
    protected $template = NULL;

    /**
     * @var string
     */
    protected $host = '%s';

    /**
     * @param string $host
     *
     * @return ResetPasswordMessage
     */
    public function setHost(string $host): ResetPasswordMessage
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return array
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
    public function setSubject(string $subject): ResetPasswordMessage
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param null|string $template
     *
     * @return ResetPasswordMessage
     */
    public function setTemplate(?string $template): ResetPasswordMessage
    {
        $this->template = $template;

        return $this;
    }

}
