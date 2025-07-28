<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class TmpUser
 *
 * @package Hanaboso\UserBundle\Entity
 */
#[ORM\Entity(repositoryClass: 'Hanaboso\UserBundle\Repository\Entity\TmpUserRepository')]
#[ORM\Table(name: 'tmp_user')]
class TmpUser extends UserAbstract
{

    /**
     * @var Token|null
     */
    #[ORM\OneToOne(inversedBy: 'tmpUser', targetEntity: 'Hanaboso\UserBundle\Entity\Token')]
    protected ?Token $token = NULL;

    /**
     * @param self $tmpUser
     *
     * @return self
     */
    public static function from(self $tmpUser): self
    {
        $tmpUser;

        return new self();
    }

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
     * @return self
     */
    public function setPassword(string $pwd): self
    {
        $pwd;

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
