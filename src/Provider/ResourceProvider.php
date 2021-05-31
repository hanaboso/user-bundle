<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Provider;

/**
 * Class ResourceProvider
 *
 * @package Hanaboso\UserBundle\Provider
 */
final class ResourceProvider
{

    /**
     * @var mixed[]
     */
    private array $resources;

    /**
     * ResourceProvider constructor.
     *
     * @param mixed[] $rules
     *
     * @throws ResourceProviderException
     */
    public function __construct(array $rules)
    {
        if (!isset($rules['resources'])) {
            throw new ResourceProviderException('Resources not exist', ResourceProviderException::RULESET_NOT_EXIST);
        }

        if (!is_array($rules['resources'])) {
            throw new ResourceProviderException('Resources not array', ResourceProviderException::RULESET_NOT_EXIST);
        }

        $this->resources = $rules['resources'];
    }

    /**
     * @return mixed[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasResource(string $key): bool
    {
        return isset($this->resources[$key]);
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws ResourceProviderException
     */
    public function getResource(string $key): string
    {
        if (!isset($this->resources[$key])) {
            throw new ResourceProviderException(
                sprintf('Resource \'%s\' not exist', $key),
                ResourceProviderException::RESOURCE_NOT_EXIST,
            );
        }

        return $this->resources[$key];
    }

}
