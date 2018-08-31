<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Hanaboso\CommonsBundle\Traits\Entity\IdTrait;

/**
 * Class UserAbstract
 *
 * @package Hanaboso\UserBundle\Entity
 */
abstract class UserAbstract implements UserInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * UserAbstract constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime();
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
     * Needed by symfony's UserInterface.
     *
     * @return array
     */
    public function getRoles(): array
    {
        return [];
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
        throw new Exception('UserAbstract::eraseCredentials is not implemented');
    }

    /**
     * @param bool $deleted
     *
     * @return UserInterface
     */
    public function setDeleted(bool $deleted): UserInterface
    {
        $deleted;

        return $this;
    }

}