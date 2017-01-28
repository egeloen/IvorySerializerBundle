<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\CacheWarmer;

use Ivory\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Ivory\Serializer\Mapping\Loader\ClassMetadataLoaderInterface;
use Ivory\Serializer\Mapping\Loader\MappedClassMetadataLoaderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class SerializerCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $factory;

    /**
     * @var ClassMetadataLoaderInterface
     */
    private $loader;

    /**
     * @var CacheItemPoolInterface
     */
    private $pool;

    /**
     * @param ClassMetadataFactoryInterface $factory
     * @param ClassMetadataLoaderInterface  $loader
     * @param CacheItemPoolInterface        $pool
     */
    public function __construct(
        ClassMetadataFactoryInterface $factory,
        ClassMetadataLoaderInterface $loader,
        CacheItemPoolInterface $pool
    ) {
        $this->factory = $factory;
        $this->loader = $loader;
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        if (!$this->loader instanceof MappedClassMetadataLoaderInterface) {
            return;
        }

        foreach ($this->loader->getMappedClasses() as $class) {
            $this->factory->getClassMetadata($class);
        }

        $this->pool->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
