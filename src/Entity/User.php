<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Hanaboso\CommonsBundle\Traits\Entity\DeletedTrait;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class User
 *
 * @package Hanaboso\UserBundle\Entity
 *
 * @ORM\Table(name="`user`")
 * @ORM\Entity(repositoryClass="Hanaboso\UserBundle\Repository\Entity\UserRepository")
 */
class User extends UserAbstract
{

    use DeletedTrait;

    /**
     * @var TokenInterface|null
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\UserBundle\Entity\Token", inversedBy="user")
     */
    protected $token;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $password;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", columnDefinition="DATETIME ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP")
     */
    private $updated;

    /**
     * @param TmpUserInterface $tmpUser
     *
     * @return UserInterface
     */
    public static function from(TmpUserInterface $tmpUser): UserInterface
    {
        $user = (new self())
            ->setEmail($tmpUser->getEmail());

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
     * @param string $password
     *
     * @return UserInterface
     */
    public function setPassword(string $password): UserInterface
    {
        $this->password = $password;

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
     * @param bool $deleted
     *
     * @return UserInterface
     */
    public function setDeleted(bool $deleted): UserInterface
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'       => $this->getId(),
            'email'    => $this->getEmail(),
        ];
    }

}