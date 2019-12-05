<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Messages;

use Hanaboso\UserBundle\Model\MessageSubject;

/**
 * Class RegisterMessage
 *
 * @package Hanaboso\UserBundle\Model\Messages
 */
class RegisterMessage extends UserMessageAbstract
{

    /**
     * @var string
     */
    protected $subject = MessageSubject::USER_REGISTER;

    /**
     * @var string|null
     */
    protected $template = NULL;

    /**
     * @return mixed[]
     */
    public function getMessage(): array
    {
        $this->message['to'] = $this->user->getEmail();

        return $this->message;
    }

}
