<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\Provider;

use Hanaboso\UserBundle\Exception\UserException;

/**
 * Class ResourceProvider
 *
 * @package Hanaboso\PipesFramework\Acl\Provider
 */
class ResourceProvider
{

    /**
     * @var array
     */
    private $resources;

    /**
     * ResourceProvider constructor.
     *
     * @param array $rules
     *
     * @throws UserException
     */
    public function __construct(array $rules)
    {
        if (!isset($rules['resources'])) {
            throw new UserException('Resources not exist', UserException::RULESET_NOT_EXIST);
        }

        if (!is_array($rules['resources'])) {
            throw new UserException('Resources not array', UserException::RULESET_NOT_EXIST);
        }

        $this->resources = $rules['resources'];
    }

    /**
     * @return array
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
     * @throws UserException
     */
    public function getResource(string $key): string
    {
        if (!isset($this->resources[$key])) {
            throw new UserException(
                sprintf('Resource \'%s\' not exist', $key),
                UserException::RESOURCE_NOT_EXIST
            );
        }

        return $this->resources[$key];
    }

}