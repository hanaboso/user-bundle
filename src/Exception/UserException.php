<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;

/**
 * Class UserException
 *
 * @package Hanaboso\UserBundle\Exception
 */
class UserException extends PipesFrameworkException
{

    protected const OFFSET = 1900;

    public const RESOURCE_NOT_EXIST = self::OFFSET + 1;
    public const RULESET_NOT_EXIST  = self::OFFSET + 2;

}