<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Enum;

use Hanaboso\UserBundle\Enum\EnumAbstract;

/**
 * Class TestEnum
 *
 * @package UserBundleTests\Unit\Enum
 */
final class TestEnum extends EnumAbstract
{

    /**
     * @var string[]
     */
    protected static array $choices = ['first' => '1st', 'second' => '2nd', 'third' => '3rd'];

}
