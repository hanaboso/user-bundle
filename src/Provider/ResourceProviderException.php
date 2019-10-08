<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Provider;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class ProviderException
 *
 * @package Hanaboso\UserBundle\Provider
 */
class ResourceProviderException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1900;

    public const RESOURCE_NOT_EXIST = self::OFFSET + 1;
    public const RULESET_NOT_EXIST  = self::OFFSET + 2;

}
