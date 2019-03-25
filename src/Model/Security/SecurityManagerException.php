<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Security;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class SecurityManagerException
 *
 * @package Hanaboso\UserBundle\Model\Security
 */
final class SecurityManagerException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1400;

    public const USER_NOT_LOGGED            = self::OFFSET + 1;
    public const USER_OR_PASSWORD_NOT_VALID = self::OFFSET + 2;
    public const USER_ENCODER_NOT_FOUND     = self::OFFSET + 3;

}