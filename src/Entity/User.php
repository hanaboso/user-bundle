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
     * @var TokenInterface|null
     */
    #[ORM\OneToOne(inversedBy: 'user', targetEntity: 'Hanaboso\UserBundle\Entity\Token')]
    protected ?TokenInterface $token = NULL;

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
     * @return UserInterface
     */
    public function setPassword(string $pwd): UserInterface
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
     * @return UserInterface
     */
    public function setUpdated(DateTime $updated): UserInterface
    {
        $this->updated = $updated;

        return $this;
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

    /**
     * @param TmpUserInterface $tmpUser
     *
     * @return UserInterface
     */
    public static function from(TmpUserInterface $tmpUser): UserInterface
    {
        return (new self())->setEmail($tmpUser->getEmail());
    }

}
