<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Entity;

/**
 * Class TmpUser
 *
 * @package Hanaboso\UserBundle\Entity
 */
interface TmpUserInterface extends UserInterface
{

    /**
     * @return string
     */
    public function getType(): string;

}