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

use Ivory\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\CachePoolPass;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class RegisterCachePoolPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($classMetadataFactoryService = 'ivory.serializer.mapping.factory')) {
            return;
        }

        $classMetadataFactory = $container->getDefinition($classMetadataFactoryService);

        if ($classMetadataFactory->getClass() !== CacheClassMetadataFactory::class) {
            return;
        }

        $cache = (string) $classMetadataFactory->getArgument(1);

        if (class_exists(CachePoolPass::class) || $container->hasDefinition($cache)) {
            return;
        }

        $cachePath = $container->getParameter('kernel.cache_dir').'/ivory-serializer';

        $container->setDefinition(
            $cache = 'ivory.serializer.cache',
            new Definition(FilesystemAdapter::class, ['', 0, $cachePath])
        );

        $classMetadataFactory->replaceArgument(1, $cachePool = new Reference($cache));

        $container
            ->getDefinition('ivory.serializer.cache_warmer')
            ->replaceArgument(2, $cachePool);
    }
}
