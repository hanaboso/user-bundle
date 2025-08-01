<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Messages;

use Hanaboso\UserBundle\Document\Token as DmToken;
use Hanaboso\UserBundle\Entity\Token;
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
    protected string $subject = MessageSubject::USER_ACTIVATE;

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
     * @return ActivateMessage
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
        $this->message['to'] = $this->user->getEmail();
        /** @var Token|DmToken|null $token */
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
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string|null $template
     *
     * @return ActivateMessage
     */
    public function setTemplate(?string $template): self
    {
        $this->template = $template;

        return $this;
    }

}
