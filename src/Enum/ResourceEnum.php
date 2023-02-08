<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Enum;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\UserBundle\Enum
 */
class ResourceEnum extends EnumAbstract
{

    public const USER     = 'user';
    public const TMP_USER = 'tmp_user';
    public const TOKEN    = 'token';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::USER     => 'User entity',
        self::TMP_USER => 'TmpUser entity',
        self::TOKEN    => 'Token entity',
    ];

}
