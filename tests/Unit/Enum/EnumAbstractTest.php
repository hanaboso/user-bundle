<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Enum;

use Hanaboso\UserBundle\Enum\EnumAbstract;
use Hanaboso\Utils\Exception\EnumException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class EnumAbstractTest
 *
 * @package UserBundleTests\Unit\Enum
 */
#[CoversClass(EnumAbstract::class)]
final class EnumAbstractTest extends TestCase
{

    /**
     * @return void
     */
    public function testGetChoices(): void
    {
        self::assertEquals(['first' => '1st', 'second' => '2nd', 'third' => '3rd'], TestEnum::getChoices());
    }

    /**
     * @throws EnumException
     */
    public function testIsValid(): void
    {
        self::assertSame('first', TestEnum::isValid('first'));
    }

    /**
     * @throws EnumException
     */
    public function testIsValidErr(): void
    {
        $this->expectException(EnumException::class);
        TestEnum::isValid('fourth');
    }

}
