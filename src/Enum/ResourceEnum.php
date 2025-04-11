<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Enum;

/**
 * Class ResourceEnum
 *
 * @package Hanaboso\UserBundle\Enum
 */
class ResourceEnum extends EnumAbstract
{

    public const string USER     = 'user';
    public const string TMP_USER = 'tmp_user';
    public const string TOKEN    = 'token';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::TMP_USER => 'TmpUser entity',
        self::TOKEN    => 'Token entity',
        self::USER     => 'User entity',
    ];

}
