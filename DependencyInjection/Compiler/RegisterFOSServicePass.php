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

use Ivory\SerializerBundle\FOS\Type\ExceptionType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class RegisterFOSServicePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('fos_rest.exception.messages_map')) {
            $container->removeDefinition('ivory.serializer.fos');

            return;
        }

        $exceptionType = $container->getDefinition('ivory.serializer.type.exception');
        $exceptionType
            ->setClass(ExceptionType::class)
            ->setArguments([
                new Reference('fos_rest.exception.messages_map'),
                $exceptionType->getArgument(0),
            ]);
    }
}
