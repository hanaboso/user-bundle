<?php declare(strict_types=1);

namespace UserBundleTests\Unit\Provider;

use Exception;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\UserBundle\Provider\ResourceProviderException;
use UserBundleTests\KernelTestCaseAbstract;

/**
 * Class ResourceProviderTest
 *
 * @package UserBundleTests\Unit\Provider
 *
 * @covers  \Hanaboso\UserBundle\Provider\ResourceProvider
 */
final class ResourceProviderTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Provider\ResourceProvider::getResource
     */
    public function testGetResources(): void
    {
        self::assertEquals([], (new ResourceProvider(['resources' => []]))->getResources());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Provider\ResourceProvider::getResource
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
     *
     * @covers \Hanaboso\UserBundle\Provider\ResourceProvider::getResource
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
     *
     * @covers \Hanaboso\UserBundle\Provider\ResourceProvider::getResource
     */
    public function testGetResource(): void
    {
        self::assertEquals('value', (new ResourceProvider(['resources' => ['key' => 'value']]))->getResource('key'));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\UserBundle\Provider\ResourceProvider::getResource
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
     *
     * @covers \Hanaboso\UserBundle\Provider\ResourceProvider::hasResource
     */
    public function testHasResource(): void
    {
        self::assertTrue((new ResourceProvider(['resources' => ['key' => 'value']]))->hasResource('key'));
    }

}
