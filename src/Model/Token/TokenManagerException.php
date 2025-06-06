<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Model\Token;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TokenManagerException
 *
 * @package Hanaboso\UserBundle\Model\Token
 */
final class TokenManagerException extends PipesFrameworkExceptionAbstract
{

    public const int TOKEN_NOT_VALID    = self::OFFSET + 1;
    public const int TOKEN_ALREADY_USED = self::OFFSET + 2;

    protected const int OFFSET = 1_100;

}
