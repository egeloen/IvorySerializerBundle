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

use Ivory\Serializer\Direction;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class RegisterVisitorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $mapping = [
            'serialization'   => Direction::SERIALIZATION,
            'deserialization' => Direction::DESERIALIZATION,
        ];

        $typeRegistry = $container->getDefinition('ivory.serializer.registry.visitor');

        foreach ($container->findTaggedServiceIds($tag = 'ivory.serializer.visitor') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['direction'])) {
                    throw new \RuntimeException(sprintf(
                        'No "direction" attribute found for the tag "%s" on the service "%s".',
                        $tag,
                        $id
                    ));
                }

                if (!isset($mapping[$attribute['direction']])) {
                    throw new \RuntimeException(sprintf(
                        'The "direction" attribute (%s) found for the tag "%s" on the service "%s" is not valid (Supported: %s).',
                        $attribute['direction'],
                        $tag,
                        $id,
                        implode(', ', array_keys($mapping))
                    ));
                }

                if (!isset($attribute['format'])) {
                    throw new \RuntimeException(sprintf(
                        'No "format" attribute found for the tag "%s" on the service "%s".',
                        $tag,
                        $id
                    ));
                }

                $typeRegistry->addMethodCall(
                    'registerVisitor',
                    [$mapping[$attribute['direction']], $attribute['format'], new Reference($id)]
                );
            }
        }
    }
}
