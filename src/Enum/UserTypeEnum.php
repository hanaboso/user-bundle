<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Enum;

/**
 * Class UserTypeEnum
 *
 * @package Hanaboso\UserBundle\Enum
 */
final class UserTypeEnum extends EnumAbstract
{

    public const string USER     = 'user';
    public const string TMP_USER = 'tmpUser';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::TMP_USER => 'Unactivated user',
        self::USER     => 'User',
    ];

}
