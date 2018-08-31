<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Traits\Document\DeletedTrait;
use Hanaboso\UserBundle\Entity\TmpUserInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class User
 *
 * @package Hanaboso\UserBundle\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\UserBundle\Repository\Document\UserRepository")
 */
class User extends UserAbstract
{

    use DeletedTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $password;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    private $updated;

    /**
     * @param TmpUserInterface $tmpUser
     *
     * @return UserInterface
     */
    public static function from(TmpUserInterface $tmpUser): UserInterface
    {
        return (new self())->setEmail($tmpUser->getEmail());
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
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'    => $this->getId(),
            'email' => $this->getEmail(),
        ];
    }

}

