<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

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
    protected static $choices = [
        self::USER     => 'User entity',
        self::TMP_USER => 'TmpUser entity',
        self::TOKEN    => 'Token entity',
    ];

}