<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Provider;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class ResourceProviderException
 *
 * @package Hanaboso\UserBundle\Provider
 */
final class ResourceProviderException extends PipesFrameworkExceptionAbstract
{

    public const RESOURCE_NOT_EXIST = self::OFFSET + 1;
    public const RULESET_NOT_EXIST  = self::OFFSET + 2;

    protected const OFFSET = 1_900;

}
