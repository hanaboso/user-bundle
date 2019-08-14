<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Messages;

use Hanaboso\UserBundle\Entity\TokenInterface;
use Hanaboso\UserBundle\Model\MessageSubject;

/**
 * Class ActivateMessage
 *
 * @package Hanaboso\UserBundle\Model\Messages
 */
class ActivateMessage extends UserMessageAbstract
{

    /**
     * @var string
     */
    protected $subject = MessageSubject::USER_ACTIVATE;

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
     * @return ActivateMessage
     */
    public function setHost(string $host): ActivateMessage
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return array
     */
    public function getMessage(): array
    {
        $this->message['to'] = $this->user->getEmail();
        /** @var TokenInterface|null $token */
        $token = $this->user->getToken();

        $this->message['dataContent']['link'] = sprintf($this->host, $token ? $token->getHash() : '');
        $this->message['subject']             = $this->subject ?? '';
        $this->message['template']            = $this->template ?? '';

        return $this->message;
    }

    /**
     * @param string $subject
     *
     * @return ActivateMessage
     */
    public function setSubject(string $subject): ActivateMessage
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param null|string $template
     *
     * @return ActivateMessage
     */
    public function setTemplate(?string $template): ActivateMessage
    {
        $this->template = $template;

        return $this;
    }

}
