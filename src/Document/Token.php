<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\UserBundle\Entity\TmpUserInterface;
use Hanaboso\UserBundle\Entity\TokenInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use LogicException;

/**
 * Class Token
 *
 * @package Hanaboso\UserBundle\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\UserBundle\Repository\Document\TokenRepository")
 */
class Token implements TokenInterface
{

    use IdTrait;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $created;

    /**
     * @var UserInterface|null
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\UserBundle\Document\User")
     */
    private $user;

    /**
     * @var UserInterface|TmpUserInterface|null
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\UserBundle\Document\TmpUser")
     */
    private $tmpUser;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $hash;

    /**
     * Token constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUTCDateTime();
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
        } else if ($this->tmpUser) {
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
