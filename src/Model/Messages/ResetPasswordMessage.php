<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 14.9.17
 * Time: 11:39
 */

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
     * @return array
     */
    public function getMessage(): array
    {
        $this->message["to"]                      = $this->user->getEmail();
        $this->message["dataContent"]["username"] = $this->user->getUsername();

        return $this->message;
    }

}
