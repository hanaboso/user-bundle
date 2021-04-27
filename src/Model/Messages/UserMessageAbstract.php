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
    protected string $subject;

    /**
     * @var string|null
     */
    protected ?string $template;

    /**
     * @var string
     */
    protected string $from = '';

    /**
     * @var mixed[]
     */
    protected array $message = [
        'to'          => '',
        'subject'     => '',
        'content'     => '',
        'dataContent' => [],
        'template'    => '',
        'from'        => '',
    ];

    /**
     * @return mixed[]
     */
    abstract public function getMessage(): array;

    /**
     * UserMessageAbstract constructor.
     *
     * @param UserInterface $user
     */
    public function __construct(protected UserInterface $user)
    {
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
