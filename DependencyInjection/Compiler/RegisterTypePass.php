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

        foreach ($container->findTaggedServiceIds($tag = 'ivory.serializer.type') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new \RuntimeException(sprintf(
                        'No "alias" attribute found for the tag "%s" on the service "%s".',
                        $tag,
                        $id
                    ));
                }

                if ($attribute['alias'] === '!null') {
                    $attribute['alias'] = 'null';
                }

                $typeRegistry->addMethodCall('registerType', [$attribute['alias'], new Reference($id)]);
            }
        }
    }
}
