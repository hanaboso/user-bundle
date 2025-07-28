<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class User
 *
 * @package Hanaboso\UserBundle\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\UserBundle\Repository\Document\UserRepository')]
class User extends UserAbstract
{

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    protected string $password;

    /**
     * @var DateTime
     */
    #[ODM\Field(type: 'date')]
    private DateTime $updated;

    /**
     * @param TmpUser $tmpUser
     *
     * @return self
     */
    public static function from(TmpUser $tmpUser): self
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
     * @return string
     */
    public function getPassword(): string
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
