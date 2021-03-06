<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface as SecurityCoreUserInterface;

/**
 * Interface UserInterface
 *
 * @package Hanaboso\UserBundle\Entity
 */
interface UserInterface extends SecurityCoreUserInterface
{

    /**
     * @param TmpUserInterface $tmpUser
     *
     * @return UserInterface
     */
    public static function from(TmpUserInterface $tmpUser): UserInterface;

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getEmail(): string;

    /**
     * @param string $email
     *
     * @return UserInterface
     */
    public function setEmail(string $email): UserInterface;

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime;

    /**
     * @param string $pwd
     *
     * @return UserInterface
     */
    public function setPassword(string $pwd): UserInterface;

    /**
     * @return TokenInterface|null
     */
    public function getToken(): ?TokenInterface;

    /**
     * @param TokenInterface|null $token
     *
     * @return UserInterface
     */
    public function setToken(?TokenInterface $token): UserInterface;

    /**
     * @param bool $deleted
     *
     * @return UserInterface
     */
    public function setDeleted(bool $deleted): self;

    /**
     * @return mixed[]
     */
    public function toArray(): array;

}
