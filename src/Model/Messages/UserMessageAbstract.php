<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Messages;

use Hanaboso\UserBundle\Entity\UserInterface;

/**
 * Class UserMessageAbstract
 *
 * @package Hanaboso\UserBundle\Model\Messages
 */
abstract class UserMessageAbstract
{

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var string
     */
    protected $from = '';

    /**
     * @var mixed[]
     */
    protected $message = [
        'to'          => '',
        'subject'     => '',
        'content'     => '',
        'dataContent' => [],
        'template'    => '',
        'from'        => '',
    ];

    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @return mixed[]
     */
    abstract public function getMessage(): array;

    /**
     * UserMessageAbstract constructor.
     *
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user)
    {
        $this->user                = $user;
        $this->message['subject']  = $this->subject;
        $this->message['template'] = $this->template;
    }

    /**
     * @param string $from
     *
     * @return UserMessageAbstract
     */
    public function setFrom(string $from): UserMessageAbstract
    {
        $this->from = $from;

        return $this;
    }

}
