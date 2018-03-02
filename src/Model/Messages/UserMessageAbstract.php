<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 14.9.17
 * Time: 13:07
 */

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
     * @var array
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

    /**
     * @return array
     */
    abstract public function getMessage(): array;

}
