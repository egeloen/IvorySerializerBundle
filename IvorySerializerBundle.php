<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle;

use Ivory\SerializerBundle\DependencyInjection\Compiler\RegisterCachePoolPass;
use Ivory\SerializerBundle\DependencyInjection\Compiler\RegisterClassMetadataLoaderPass;
use Ivory\SerializerBundle\DependencyInjection\Compiler\RegisterFOSServicePass;
use Ivory\SerializerBundle\DependencyInjection\Compiler\RegisterListenerPass;
use Ivory\SerializerBundle\DependencyInjection\Compiler\RegisterTypePass;
use Ivory\SerializerBundle\DependencyInjection\Compiler\RegisterVisitorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class IvorySerializerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(new RegisterCachePoolPass())
            ->addCompilerPass(new RegisterClassMetadataLoaderPass())
            ->addCompilerPass(new RegisterListenerPass())
            ->addCompilerPass(new RegisterFOSServicePass())
            ->addCompilerPass(new RegisterTypePass())
            ->addCompilerPass(new RegisterVisitorPass());
    }
}
