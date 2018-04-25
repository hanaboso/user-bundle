<?php declare(strict_types=1);

namespace Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use EmailServiceBundle\EmailServiceBundle;
use Hanaboso\CommonsBundle\HbPFCommonsBundle;
use Hanaboso\UserBundle\HbPFUserBundle;
use RabbitMqBundle\RabbitMqBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * Class Kernel
 *
 * @package Tests
 */
class Kernel extends BaseKernel
{

    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment;
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->getProjectDir() . '/var/log';
    }

    /**
     * @return \Generator|\Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function registerBundles(): iterable
    {
        $contents = [
            FrameworkBundle::class       => ['all' => TRUE],
            SecurityBundle::class        => ['all' => TRUE],
            DoctrineCacheBundle::class   => ['all' => TRUE],
            DoctrineBundle::class        => ['all' => TRUE],
            MonologBundle::class         => ['all' => TRUE],
            DoctrineMongoDBBundle::class => ['all' => TRUE],
            HbPFCommonsBundle::class     => ['all' => TRUE],
            RabbitMqBundle::class        => ['all' => TRUE],
            SwiftmailerBundle::class     => ['all' => TRUE],
            EmailServiceBundle::class    => ['all' => TRUE],
            HbPFUserBundle::class        => ['all' => TRUE],

        ];
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     *
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('container.dumper.inline_class_loader', TRUE);
        $confDir = $this->getProjectDir() . '/tests/testApp/config';
        $loader->load($confDir . '/*' . self::CONFIG_EXTS, 'glob');
    }

    /**
     * @param RouteCollectionBuilder $routes
     *
     * @throws \Symfony\Component\Config\Exception\FileLoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir() . '/tests/testApp/routing';
        $routes->import($confDir . '/*' . self::CONFIG_EXTS, '/', 'glob');
    }

}