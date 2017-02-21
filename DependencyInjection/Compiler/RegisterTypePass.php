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
class RegisterTypePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $typeRegistry = $container->getDefinition('ivory.serializer.registry.type');
        $types = [];

        foreach ($container->findTaggedServiceIds($tag = 'ivory.serializer.type') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $priority = isset($attribute['priority']) ? $attribute['priority'] : 0;

                if (!isset($attribute['alias'])) {
                    throw new \RuntimeException(sprintf(
                        'No "alias" attribute found for the tag "%s" on the service "%s".',
                        $tag,
                        $id
                    ));
                }

                if (!isset($attribute['direction'])) {
                    $attribute['direction'] = 'all';
                }

                $mapping = [
                    'all'             => [Direction::SERIALIZATION, Direction::DESERIALIZATION],
                    'serialization'   => [Direction::SERIALIZATION],
                    'deserialization' => [Direction::DESERIALIZATION],
                ];

                if (!isset($mapping[$attribute['direction']])) {
                    throw new \RuntimeException(sprintf(
                        'The "direction" attribute for the tag "%s" on the service "%s" is not valid (%s).',
                        $tag,
                        $id,
                        $attribute['direction']
                    ));
                }

                if ($attribute['alias'] === '!null') {
                    $attribute['alias'] = 'null';
                }

                foreach ($mapping[$attribute['direction']] as $direction) {
                    $types[$direction][$priority][$id][] = $attribute;
                }
            }
        }

        foreach ($types as $direction => $sortedTypes) {
            krsort($sortedTypes);
            $sortedTypes = call_user_func_array('array_merge', $sortedTypes);

            foreach ($sortedTypes as $id => $attributes) {
                foreach ($attributes as $attribute) {
                    $typeRegistry->addMethodCall('registerType', [$attribute['alias'], $direction, new Reference($id)]);
                }
            }
        }
    }
}
