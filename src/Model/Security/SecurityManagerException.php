<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Security;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class SecurityManagerException
 *
 * @package Hanaboso\UserBundle\Model\Security
 */
final class SecurityManagerException extends PipesFrameworkExceptionAbstract
{

    public const int USER_NOT_LOGGED            = self::OFFSET + 1;
    public const int USER_OR_PASSWORD_NOT_VALID = self::OFFSET + 2;
    public const int USER_ENCODER_NOT_FOUND     = self::OFFSET + 3;

    protected const int OFFSET = 1_400;

}
