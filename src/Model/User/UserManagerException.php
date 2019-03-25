<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\User;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class UserManagerException
 *
 * @package Hanaboso\UserBundle\Model\User
 */
final class UserManagerException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1200;

    public const USER_NOT_EXISTS           = self::OFFSET + 1;
    public const USER_EMAIL_NOT_EXISTS     = self::OFFSET + 2;
    public const USER_EMAIL_ALREADY_EXISTS = self::OFFSET + 3;
    public const USER_DELETE_NOT_ALLOWED   = self::OFFSET + 4;

}