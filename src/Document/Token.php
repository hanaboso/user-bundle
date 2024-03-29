<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\UserBundle\Entity\TmpUserInterface;
use Hanaboso\UserBundle\Entity\TokenInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
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
class Token implements TokenInterface
{

    use IdTrait;

    /**
     * @var DateTime
     */
    #[ODM\Field(type: 'date')]
    private DateTime $created;

    /**
     * @var UserInterface|null
     */
    #[ODM\ReferenceOne(targetDocument: 'Hanaboso\UserBundle\Document\User')]
    private ?UserInterface $user = NULL;

    /**
     * @var TmpUserInterface|null
     */
    #[ODM\ReferenceOne(targetDocument: 'Hanaboso\UserBundle\Document\TmpUser')]
    private ?TmpUserInterface $tmpUser = NULL;

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
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     *
     * @return TokenInterface
     */
    public function setUser(UserInterface $user): TokenInterface
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return TmpUserInterface|null
     */
    public function getTmpUser(): ?TmpUserInterface
    {
        return $this->tmpUser;
    }

    /**
     * @param TmpUserInterface|null $tmpUser
     *
     * @return TokenInterface
     */
    public function setTmpUser(?TmpUserInterface $tmpUser): TokenInterface
    {
        $this->tmpUser = $tmpUser;

        return $this;
    }

    /**
     * @return UserInterface|TmpUserInterface
     */
    public function getUserOrTmpUser(): UserInterface|TmpUserInterface
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
     * @param UserInterface|TmpUserInterface $user
     *
     * @return TokenInterface
     */
    public function setUserOrTmpUser(UserInterface|TmpUserInterface $user): TokenInterface
    {
        if ($user->getType() === UserTypeEnum::USER) {
            $this->setUser($user);
        } else if ($user->getType() === UserTypeEnum::TMP_USER) {
            /** @var TmpUserInterface $tmpUser */
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
