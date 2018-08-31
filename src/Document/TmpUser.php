<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\UserBundle\Entity\TmpUserInterface;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class TmpUser
 *
 * @package Hanaboso\UserBundle\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\UserBundle\Repository\Document\TmpUserRepository")
 */
class TmpUser extends UserAbstract implements TmpUserInterface
{

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
     * @return array
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