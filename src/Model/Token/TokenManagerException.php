<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Token;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TokenManagerException
 *
 * @package Hanaboso\UserBundle\Model\Token
 */
final class TokenManagerException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 1100;

    public const TOKEN_NOT_VALID    = self::OFFSET + 1;
    public const TOKEN_ALREADY_USED = self::OFFSET + 2;

}
