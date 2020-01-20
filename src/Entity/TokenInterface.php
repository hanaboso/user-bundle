<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

use DateTime;

/**
 * Interface TokenInterface
 *
 * @package Hanaboso\UserBundle\Entity
 */
interface TokenInterface
{

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime;

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface;

    /**
     * @param UserInterface $user
     *
     * @return TokenInterface
     */
    public function setUser(UserInterface $user): TokenInterface;

    /**
     * @return TmpUserInterface|null
     */
    public function getTmpUser(): ?TmpUserInterface;

    /**
     * @param TmpUserInterface|null $tmpUser
     *
     * @return TokenInterface
     */
    public function setTmpUser(?TmpUserInterface $tmpUser): TokenInterface;

    /**
     * @return UserInterface|TmpUserInterface
     */
    public function getUserOrTmpUser(): UserInterface;

    /**
     * @param UserInterface|TmpUserInterface $user
     *
     * @return TokenInterface
     */
    public function setUserOrTmpUser(UserInterface $user): TokenInterface;

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getHash(): string;

}
