<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class TmpUser
 *
 * @package Hanaboso\UserBundle\Entity
 *
 * @ORM\Table(name="tmp_user")
 * @ORM\Entity(repositoryClass="Hanaboso\UserBundle\Repository\Entity\TmpUserRepository")
 */
class TmpUser extends UserAbstract implements TmpUserInterface
{

    /**
     * @var TokenInterface|null
     *
     * @ORM\OneToOne(targetEntity="Hanaboso\UserBundle\Entity\Token", inversedBy="tmpUser")
     */
    protected ?TokenInterface $token = NULL;

    /**
     * @return string
     */
    public function getType(): string
    {
        return UserTypeEnum::TMP_USER;
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return '';
    }

    /**
     * @param string $pwd
     *
     * @return UserInterface
     */
    public function setPassword(string $pwd): UserInterface
    {
        $pwd;

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
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id'    => $this->getId(),
            'email' => $this->getEmail(),
        ];
    }

    /**
     * @param TmpUserInterface $tmpUser
     *
     * @return UserInterface
     */
    public static function from(TmpUserInterface $tmpUser): UserInterface
    {
        $tmpUser;

        return new self();
    }

}
