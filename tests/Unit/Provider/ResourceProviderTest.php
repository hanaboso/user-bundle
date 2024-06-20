<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Provider;

use Exception;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use PHPUnit\Framework\Attributes\CoversClass;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class ResourceProviderTest
 *
 * @package UserBundleTests\Unit\Provider
 */
#[CoversClass(ResourceProvider::class)]
final class ResourceProviderTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetResources(): void
    {
        self::assertEquals([], (new ResourceProvider(['resources' => []]))->getResources());
    }

    /**
     * @throws Exception
     */
    public function testGetResourcesException(): void
    {
        self::expectException(ResourceProviderException::class);
        self::expectExceptionCode(ResourceProviderException::RULESET_NOT_EXIST);
        self::expectExceptionMessage('Resources not exist');

        new ResourceProvider([]);
    }

    /**
     * @throws Exception
     */
    public function testGetResourcesExceptionSecond(): void
    {
        self::expectException(ResourceProviderException::class);
        self::expectExceptionCode(ResourceProviderException::RULESET_NOT_EXIST);
        self::expectExceptionMessage('Resources not array');

        new ResourceProvider(['resources' => '']);
    }

    /**
     * @throws Exception
     */
    public function testGetResource(): void
    {
        self::assertEquals('value', (new ResourceProvider(['resources' => ['key' => 'value']]))->getResource('key'));
    }

    /**
     * @throws Exception
     */
    public function testGetResourceException(): void
    {
        self::expectException(ResourceProviderException::class);
        self::expectExceptionCode(ResourceProviderException::RESOURCE_NOT_EXIST);
        self::expectExceptionMessage("Resource 'key' not exist");

        (new ResourceProvider(['resources' => []]))->getResource('key');
    }

    /**
     * @throws Exception
     */
    public function testHasResource(): void
    {
        self::assertTrue((new ResourceProvider(['resources' => ['key' => 'value']]))->hasResource('key'));
    }

}
