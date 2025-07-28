<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use LogicException;

/**
 * Class Token
 *
 * @package Hanaboso\UserBundle\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\UserBundle\Repository\Document\TokenRepository')]
class Token
{

    use IdTrait;

    /**
     * @var DateTime
     */
    #[ODM\Field(type: 'date')]
    private DateTime $created;

    /**
     * @var User|null
     */
    #[ODM\ReferenceOne(targetDocument: 'Hanaboso\UserBundle\Document\User')]
    private ?User $user = NULL;

    /**
     * @var TmpUser|null
     */
    #[ODM\ReferenceOne(targetDocument: 'Hanaboso\UserBundle\Document\TmpUser')]
    private ?TmpUser $tmpUser = NULL;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $hash;

    /**
     * Token constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
        $this->hash    = uniqid();
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return TmpUser|null
     */
    public function getTmpUser(): ?TmpUser
    {
        return $this->tmpUser;
    }

    /**
     * @param TmpUser|null $tmpUser
     *
     * @return self
     */
    public function setTmpUser(?TmpUser $tmpUser): self
    {
        $this->tmpUser = $tmpUser;

        return $this;
    }

    /**
     * @return User|TmpUser
     */
    public function getUserOrTmpUser(): User|TmpUser
    {
        if ($this->user) {
            return $this->user;
        } else if ($this->tmpUser) {
            return $this->tmpUser;
        } else {
            throw new LogicException('User is not set.');
        }
    }

    /**
     * @param User|TmpUser $user
     *
     * @return self
     */
    public function setUserOrTmpUser(User|TmpUser $user): self
    {
        if ($user->getType() === UserTypeEnum::USER) {
            /** @var User $u */
            $u = $user;
            $this->setUser($u);
        } else if ($user->getType() === UserTypeEnum::TMP_USER) {
            /** @var TmpUser $tmpUser */
            $tmpUser = $user;
            $this->setTmpUser($tmpUser);
        } else {
            throw new LogicException(sprintf("Unknown user type '%s'!", $user->getType()));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

}
