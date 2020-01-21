<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Exception;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\UserBundle\Entity\TokenInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class UserAbstract
 *
 * @package Hanaboso\UserBundle\Document
 */
abstract class UserAbstract implements UserInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $email;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    protected $created;

    /**
     * @var TokenInterface|null
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\UserBundle\Document\Token")
     */
    protected $token;

    /**
     * UserAbstract constructor.
     *
     * @throws DateTimeException
     */
    public function __construct()
    {
        $this->created = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return UserInterface|User|TmpUser
     */
    public function setEmail(string $email): UserInterface
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return TokenInterface|null
     */
    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    /**
     * @param TokenInterface|null $token
     *
     * @return UserInterface
     */
    public function setToken(?TokenInterface $token): UserInterface
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return ['admin'];
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getSalt(): string
    {
        return '';
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @throws Exception
     */
    public function eraseCredentials(): void
    {
        throw new Exception(sprintf('%s is not implemented!', __METHOD__));
    }

    /**
     * @param bool $deleted
     *
     * @return mixed
     */
    public function setDeleted(bool $deleted)
    {
        $deleted;

        return $this;
    }

}
