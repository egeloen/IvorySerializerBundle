<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\DependencyInjection;

use Ivory\Serializer\Mapping\Loader\AnnotationClassMetadataLoader;
use Ivory\Serializer\Mapping\Loader\DirectoryClassMetadataLoader;
use Ivory\Serializer\Mapping\Loader\FileClassMetadataLoader;
use Ivory\Serializer\Mapping\Loader\ReflectionClassMetadataLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class IvorySerializerExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * {@inheritdoc}
     */
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $resources = [
            'cache',
            'common',
            'event',
            'fos',
            'mapping',
            'navigator',
            'registry',
            'serializer',
            'type',
            'visitor',
        ];

        foreach ($resources as $resource) {
            $loader->load($resource.'.xml');
        }

        $this->loadEvent($config['event'], $container);
        $this->loadMapping($config['mapping'], $container);
        $this->loadTypes($config['types'], $container);
        $this->loadVisitors($config['visitors'], $container);
    }

    /**
     * @param mixed[]          $config
     * @param ContainerBuilder $container
     */
    private function loadEvent(array $config, ContainerBuilder $container)
    {
        if ($config['enabled']) {
            $container
                ->getDefinition('ivory.serializer.mapping.factory')
                ->replaceArgument(0, new Reference('ivory.serializer.mapping.factory.event'));

            $container->setAlias('ivory.serializer.navigator', 'ivory.serializer.navigator.event');

            return;
        }

        $container->removeDefinition('ivory.serializer.event.dispatcher');
        $container->removeDefinition('ivory.serializer.mapping.factory.event');
        $container->removeDefinition('ivory.serializer.navigator.event');

        $container->setAlias('ivory.serializer.navigator', 'ivory.serializer.navigator.default');
    }

    /**
     * @param mixed[]          $config
     * @param ContainerBuilder $container
     */
    private function loadMapping(array $config, ContainerBuilder $container)
    {
        $directories = $files = [];

        foreach ($this->resolveMappingPaths($config, $container) as $path) {
            if (is_dir($path)) {
                $directories[] = $path;
                $container->addResource(new DirectoryResource($path));
            } elseif (is_file($path)) {
                $files[] = $path;
                $container->addResource(new FileResource($path));
            } else {
                throw new InvalidConfigurationException(sprintf('The path "%s" does not exist.', $path));
            }
        }

        $loaders = [];
        $typeParser = new Reference('ivory.serializer.type.parser');

        if ($config['reflection']) {
            $loaders['reflection'] = new Definition(ReflectionClassMetadataLoader::class, [
                new Reference('property_info', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                $typeParser,
            ]);
        }

        if ($config['annotation']) {
            $loaders['annotation'] = new Definition(AnnotationClassMetadataLoader::class, [
                new Reference('annotation_reader'),
                $typeParser,
            ]);
        }

        if (!empty($directories)) {
            $loaders['directory'] = new Definition(DirectoryClassMetadataLoader::class, [$directories, $typeParser]);
        }

        foreach ($files as $file) {
            $loaders['file_'.sha1($file)] = new Definition(FileClassMetadataLoader::class, [$file, $typeParser]);
        }

        foreach ($loaders as $key => $loader) {
            $container->setDefinition(
                'ivory.serializer.mapping.loader.'.$key,
                $loader->addTag('ivory.serializer.loader', ['priority' => -1000])
            );
        }

        $this->loadMappingCache($config['cache'], $container);
    }

    /**
     * @param mixed[]          $config
     * @param ContainerBuilder $container
     */
    private function loadMappingCache(array $config, ContainerBuilder $container)
    {
        $cacheWarmerService = 'ivory.serializer.cache_warmer';
        $classMetadataFactoryService = 'ivory.serializer.mapping.factory';

        if ($config['debug']) {
            $container->removeDefinition($cacheWarmerService);
            $container->removeDefinition($classMetadataFactoryService);

            $container->setAlias($classMetadataFactoryService, $classMetadataFactoryService.'.default');
        } else {
            $container
                ->getDefinition($cacheWarmerService)
                ->addArgument($cachePool = new Reference($config['pool']));

            $container
                ->getDefinition($classMetadataFactoryService)
                ->addArgument($cachePool)
                ->addArgument($config['prefix']);
        }
    }

    /**
     * @param mixed[]          $config
     * @param ContainerBuilder $container
     */
    private function loadTypes(array $config, ContainerBuilder $container)
    {
        $container
            ->getDefinition('ivory.serializer.type.date_time')
            ->addArgument($config['date_time']['format'])
            ->addArgument($config['date_time']['timezone']);

        $container
            ->getDefinition('ivory.serializer.type.exception')
            ->addArgument($config['exception']['debug']);
    }

    /**
     * @param mixed[]          $config
     * @param ContainerBuilder $container
     */
    private function loadVisitors(array $config, ContainerBuilder $container)
    {
        $container
            ->getDefinition('ivory.serializer.visitor.csv.serialization')
            ->addArgument($config['csv']['delimiter'])
            ->addArgument($config['csv']['enclosure'])
            ->addArgument($config['csv']['escape_char'])
            ->addArgument($config['csv']['key_separator']);

        $container
            ->getDefinition('ivory.serializer.visitor.csv.deserialization')
            ->addArgument($config['csv']['delimiter'])
            ->addArgument($config['csv']['enclosure'])
            ->addArgument($config['csv']['escape_char'])
            ->addArgument($config['csv']['key_separator']);

        $container
            ->getDefinition('ivory.serializer.visitor.json.serialization')
            ->addArgument($config['json']['options']);

        $container
            ->getDefinition('ivory.serializer.visitor.json.deserialization')
            ->addArgument($config['json']['max_depth'])
            ->addArgument($config['json']['options']);

        $container
            ->getDefinition('ivory.serializer.visitor.xml.serialization')
            ->addArgument($config['xml']['version'])
            ->addArgument($config['xml']['encoding'])
            ->addArgument($config['xml']['format_output'])
            ->addArgument($config['xml']['root'])
            ->addArgument($config['xml']['entry'])
            ->addArgument($config['xml']['entry_attribute']);

        $container
            ->getDefinition('ivory.serializer.visitor.xml.deserialization')
            ->addArgument($config['xml']['entry'])
            ->addArgument($config['xml']['entry_attribute']);

        $container
            ->getDefinition('ivory.serializer.visitor.yaml.serialization')
            ->addArgument($config['yaml']['inline'])
            ->addArgument($config['yaml']['indent'])
            ->addArgument($config['yaml']['options']);

        $container
            ->getDefinition('ivory.serializer.visitor.yaml.deserialization')
            ->addArgument($config['yaml']['options']);
    }

    /**
     * @param mixed[]          $config
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function resolveMappingPaths(array $config, ContainerBuilder $container)
    {
        $paths = [];

        if ($config['auto']['enabled']) {
            $bundles = $container->getParameter('kernel.bundles');

            foreach ($bundles as $bundle) {
                $bundlePath = dirname((new \ReflectionClass($bundle))->getFileName());

                foreach ($config['auto']['paths'] as $relativePath) {
                    $path = $bundlePath.'/'.$relativePath;

                    if (file_exists($path)) {
                        $paths[] = $path;
                    }
                }
            }
        }

        return array_merge($paths, $config['paths']);
    }
}
