<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\InheritanceType;
use Hanaboso\UserBundle\Enum\UserTypeEnum;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class User
 *
 * @package Hanaboso\UserBundle\Entity
 */
#[ORM\HasLifecycleCallbacks()]
#[ORM\Entity(repositoryClass: 'Hanaboso\UserBundle\Repository\Entity\UserRepository')]
#[InheritanceType('SINGLE_TABLE')]
#[ORM\Table(name: '`user`')]
class User extends UserAbstract
{

    /**
     * @var Token|null
     */
    #[ORM\OneToOne(inversedBy: 'user', targetEntity: 'Hanaboso\UserBundle\Entity\Token')]
    protected ?Token $token = NULL;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: TRUE)]
    protected ?string $password = NULL;

    /**
     * @var DateTime
     */
    #[ORM\Column(type: 'datetime')]
    protected DateTime $updated;

    /**
     * @param self|TmpUser $tmpUser
     *
     * @return self
     */
    public static function from(self|TmpUser $tmpUser): self
    {
        $user = new self();
        $user->setEmail($tmpUser->getEmail());

        return $user;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return UserTypeEnum::USER;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $pwd
     *
     * @return self
     */
    public function setPassword(string $pwd): self
    {
        $this->password = $pwd;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdated(): DateTime
    {
        return $this->updated;
    }

    /**
     * @param DateTime $updated
     *
     * @return self
     */
    public function setUpdated(DateTime $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return Token|null
     */
    public function getToken(): ?Token
    {
        return $this->token;
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
     * @throws DateTimeException
     */
    #[ORM\PreFlush()]
    public function preFlush(): void
    {
        $this->updated = DateTimeUtils::getUtcDateTime();
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'email' => $this->getEmail(),
            'id'    => $this->getId(),
        ];
    }

}
