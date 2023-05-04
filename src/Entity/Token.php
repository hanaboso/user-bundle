<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Hanaboso\CommonsBundle\Database\Traits\Entity\IdTrait;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use LogicException;

/**
 * Class Token
 *
 * @package Hanaboso\UserBundle\Entity
 *
 * @ORM\Table(name="token")
 * @ORM\Entity(repositoryClass="Hanaboso\UserBundle\Repository\Entity\TokenRepository")
 */
class Token implements TokenInterface
{

    use IdTrait;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="date")
     */
    private DateTime $created;

    /**
     * @var UserInterface|null
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\UserBundle\Entity\User", mappedBy="token")
     */
    private ?UserInterface $user = NULL;

    /**
     * @var TmpUserInterface|null
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\UserBundle\Entity\TmpUser", mappedBy="token")
     */
    private ?TmpUser $tmpUser = NULL;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
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
