<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Exception;
use Hanaboso\CommonsBundle\Database\Traits\Document\DeletedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserAbstract
 *
 * @package Hanaboso\UserBundle\Document
 */
abstract class UserAbstract implements UserInterface
{

    use IdTrait;
    use DeletedTrait;

    /**
     * @var string
     */
    protected string $password;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    protected string $email;

    /**
     * @var DateTime
     */
    #[ODM\Field(type: 'date')]
    protected DateTime $created;

    /**
     * @var Token|null
     */
    #[ODM\ReferenceOne(targetDocument: 'Hanaboso\UserBundle\Document\Token')]
    protected ?Token $token = NULL;

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
     * @return Token|null
     */
    public function getToken(): ?Token
    {
        return $this->token;
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
     * @param Token|null $token
     *
     * @return self
     */
    public function setToken(?Token $token): self
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
        $this->password = '';
    }

}
