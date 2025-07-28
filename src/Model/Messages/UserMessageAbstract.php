<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Messages;

use Hanaboso\UserBundle\Document\TmpUser as DocumentTmpUser;
use Hanaboso\UserBundle\Document\User as DocumentUser;
use Hanaboso\UserBundle\Entity\TmpUser;
use Hanaboso\UserBundle\Entity\User as EntityUser;

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
        'content'     => '',
        'dataContent' => [],
        'from'        => '',
        'subject'     => '',
        'template'    => '',
        'to'          => '',
    ];

    /**
     * @return mixed[]
     */
    abstract public function getMessage(): array;

    /**
     * UserMessageAbstract constructor.
     *
     * @param EntityUser|DocumentUser|TmpUser|DocumentTmpUser $user
     */
    public function __construct(protected EntityUser|DocumentUser|TmpUser|DocumentTmpUser $user)
    {
        $this->message['subject']  = $this->subject;
        $this->message['template'] = $this->template;
    }

    /**
     * @param string $from
     *
     * @return UserMessageAbstract
     */
    public function setFrom(string $from): self
    {
        $this->from = $from;

        return $this;
    }

}
