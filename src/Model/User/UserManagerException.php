<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class UserManagerException
 *
 * @package Hanaboso\UserBundle\Model\User
 */
final class UserManagerException extends PipesFrameworkExceptionAbstract
{

    public const USER_NOT_EXISTS         = self::OFFSET + 1;
    public const USER_DELETE_NOT_ALLOWED = self::OFFSET + 4;

    protected const OFFSET = 1_200;

}
