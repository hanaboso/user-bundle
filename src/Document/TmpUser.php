<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\UserBundle\Enum\UserTypeEnum;

/**
 * Class TmpUser
 *
 * @package Hanaboso\UserBundle\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\UserBundle\Repository\Document\TmpUserRepository')]
class TmpUser extends UserAbstract
{

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
