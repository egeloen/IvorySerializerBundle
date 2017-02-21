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
        $loaders = [];

        foreach ($container->findTaggedServiceIds($tag = 'ivory.serializer.loader') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $priority = isset($attribute['priority']) ? $attribute['priority'] : 0;
                $loaders[$priority][] = new Reference($id);
            }
        }

        if (empty($loaders)) {
            throw new \RuntimeException(sprintf(
                'You must define at least one class metadata loader by enabling the reflection loader in your '.
                'configuration or by registering a loader in the container with the tag "%s".',
                $tag
            ));
        }

        $loader = 'ivory.serializer.mapping.loader';

        ksort($loaders);
        $loaders = call_user_func_array('array_merge', $loaders);

        if (count($loaders) > 1) {
            $container->setDefinition($loader, new Definition(ChainClassMetadataLoader::class, [
                $loaders,
                new Reference('ivory.serializer.type.parser'),
            ]));
        } else {
            $container->setAlias($loader, (string) array_shift($loaders));
        }
    }
}
