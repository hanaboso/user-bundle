<?php declare(strict_types=1);

namespace Hanaboso\UserBundle\DependencyInjection;

use Exception;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class HbPFUserExtension
 *
 * @package Hanaboso\UserBundle\DependencyInjection
 */
final class HbPFUserExtension extends Extension implements PrependExtensionInterface
{

    /**
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine_mongodb') && !$container->hasExtension('doctrine')) {
            throw new RuntimeException('You must register ORM or ODM (or both) before.');
        }

        $container->setParameter('src_dir', __DIR__ . '/../..');
    }

    /**
     * @param mixed[]          $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('controllers.yml');
        $loader->load('services.yml');
        $loader->load('parameters.yml');
        $loader->load('commands.yml');
        $loader->load('jwt.yml');
    }

}
