<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Hanaboso\CommonsBundle\Traits\Entity\IdTrait;
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
    private $created;

    /**
     * @var UserInterface|null
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\UserBundle\Entity\User", mappedBy="token")
     */
    private $user;

    /**
     * @var UserInterface|TmpUserInterface|null
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\UserBundle\Entity\TmpUser", mappedBy="token")
     */
    private $tmpUser;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $hash;

    /**
     * Token constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime();
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
     * @return UserInterface|TmpUserInterface|null
     */
    public function getTmpUser(): ?UserInterface
    {
        return $this->tmpUser;
    }

    /**
     * @param UserInterface|null $tmpUser
     *
     * @return TokenInterface
     */
    public function setTmpUser(?UserInterface $tmpUser): TokenInterface
    {
        $this->tmpUser = $tmpUser;

        return $this;
    }

    /**
     * @return UserInterface|TmpUserInterface
     */
    public function getUserOrTmpUser(): UserInterface
    {
        if ($this->user) {
            return $this->user;
        } elseif ($this->tmpUser) {
            return $this->tmpUser;
        } else {
            throw new LogicException('User is not set.');
        }
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

}