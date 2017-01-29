<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\DependencyInjection\Compiler;

use Ivory\Serializer\Mapping\Loader\ChainClassMetadataLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class RegisterClassMetadataLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $loaders = array_keys($container->findTaggedServiceIds($tag = 'ivory.serializer.loader'));

        if (empty($loaders)) {
            throw new \RuntimeException(sprintf(
                'You must define at least one class metadata loader by enabling the reflection loader in your '.
                'configuration or by registering a loader in the container with the tag "%s".',
                $tag
            ));
        }

        $loader = 'ivory.serializer.mapping.loader';

        if (count($loaders) > 1) {
            $container->setDefinition($loader, new Definition(ChainClassMetadataLoader::class, [
                array_map(function ($service) {
                    return new Reference($service);
                }, $loaders),
                new Reference('ivory.serializer.type.parser'),
            ]));
        } else {
            $container->setAlias($loader, array_shift($loaders));
        }
    }
}
