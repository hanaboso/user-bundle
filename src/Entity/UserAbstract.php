<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Hanaboso\CommonsBundle\Database\Traits\Entity\DeletedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Entity\IdTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserAbstract
 *
 * @package Hanaboso\UserBundle\Entity
 */
abstract class UserAbstract implements UserInterface
{

    use IdTrait;
    use DeletedTrait;

    /**
     * @var string|null
     */
    protected ?string $password;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string')]
    protected string $email;

    /**
     * @var DateTime
     */
    #[ORM\Column(type: 'datetime')]
    protected DateTime $created;

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
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        /** @var non-empty-string $email */
        $email = $this->email;

        return $email;
    }

    /**
     * @param string $email
     *
     * @return self
     */
    public function setEmail(string $email): self
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
        $this->password = NULL;
    }

}
