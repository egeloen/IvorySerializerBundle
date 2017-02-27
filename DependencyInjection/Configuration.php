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

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param bool $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = $this->createTreeBuilder();
        $treeBuilder->root('ivory_serializer')
            ->children()
            ->append($this->createEventNode())
            ->append($this->createMappingNode())
            ->append($this->createTypesNode())
            ->append($this->createVisitorsNode());

        return $treeBuilder;
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function createMappingNode()
    {
        return $this->createNode('mapping')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('annotation')->defaultValue(class_exists(AnnotationReader::class))->end()
                ->booleanNode('reflection')->defaultTrue()->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('debug')->defaultValue($this->debug)->end()
                        ->scalarNode('prefix')->defaultValue('ivory_serializer')->end()
                        ->scalarNode('pool')->defaultValue('cache.system')->end()
                    ->end()
                ->end()
                ->arrayNode('auto')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->arrayNode('paths')
                            ->prototype('scalar')->end()
                            ->defaultValue([
                                'Resources/config/ivory-serializer',
                                'Resources/config/ivory-serializer.json',
                                'Resources/config/ivory-serializer.xml',
                                'Resources/config/ivory-serializer.yml',
                            ])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('paths')
                    ->prototype('scalar')->end()
                ->end()
            ->end();
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function createEventNode()
    {
        return $this->createNode('event')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultValue(class_exists(EventDispatcher::class))->end()
            ->end();
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function createTypesNode()
    {
        return $this->createNode('types')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('date_time')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('format')->defaultValue(\DateTime::RFC3339)->end()
                        ->scalarNode('timezone')->defaultValue(date_default_timezone_get())->end()
                    ->end()
                ->end()
                ->arrayNode('exception')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('debug')->defaultValue($this->debug)->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function createVisitorsNode()
    {
        return $this->createNode('visitors')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('csv')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('delimiter')->defaultValue(',')->end()
                        ->scalarNode('enclosure')->defaultValue('"')->end()
                        ->scalarNode('escape_char')->defaultValue('\\')->end()
                        ->scalarNode('key_separator')->defaultValue('.')->end()
                    ->end()
                ->end()
                ->arrayNode('json')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_depth')->defaultValue(512)->end()
                        ->integerNode('options')->defaultValue(0)->end()
                    ->end()
                ->end()
                ->arrayNode('xml')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('version')->defaultValue('1.0')->end()
                        ->scalarNode('encoding')->defaultValue('UTF-8')->end()
                        ->booleanNode('format_output')->defaultValue($this->debug)->end()
                        ->scalarNode('root')->defaultValue('result')->end()
                        ->scalarNode('entry')->defaultValue('entry')->end()
                        ->scalarNode('entry_attribute')->defaultValue('key')->end()
                    ->end()
                ->end()
                ->arrayNode('yaml')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('inline')->defaultValue(2)->end()
                        ->integerNode('indent')->defaultValue(4)->end()
                        ->integerNode('options')->defaultValue(0)->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param string $name
     *
     * @return ArrayNodeDefinition
     */
    private function createNode($name)
    {
        return $this->createTreeBuilder()->root($name);
    }

    /**
     * @return TreeBuilder
     */
    private function createTreeBuilder()
    {
        return new TreeBuilder();
    }
}
