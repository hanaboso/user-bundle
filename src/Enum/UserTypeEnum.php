<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class UserTypeEnum
 *
 * @package Hanaboso\UserBundle\Enum
 */
final class UserTypeEnum extends EnumAbstract
{

    public const USER     = 'user';
    public const TMP_USER = 'tmpUser';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::USER     => 'User',
        self::TMP_USER => 'Unactivated user',
    ];

}