<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration for the Doctrine JSON ODM bundle.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_json_odm');
        $rootNode = $treeBuilder->getRootNode();

        /* @phpstan-ignore-next-line */
        $rootNode
            ->children()
                ->enumNode('serializer')
                    ->info('Choose the serializer to use: symfony or vuryss')
                    ->values(['symfony', 'vuryss'])
                    ->defaultValue('symfony')
                ->end()
                ->booleanNode('lazy_loading')
                    ->info('Enable lazy loading for JSON documents')
                    ->defaultTrue()
                ->end()
                ->enumNode('lazy_strategy')
                    ->info('Lazy loading strategy: ghost or proxy')
                    ->values(['ghost', 'proxy'])
                    ->defaultValue('ghost')
                ->end()
                ->arrayNode('type_map')
                    ->info('Map class names to type aliases for storage optimization')
                    ->useAttributeAsKey('alias')
                    ->scalarPrototype()->end()
                    ->example([
                        'user' => 'App\\Entity\\User',
                        'product' => 'App\\Entity\\Product',
                    ])
                ->end()
                ->arrayNode('serialization_context')
                    ->info('Default serialization context')
                    ->variablePrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('deserialization_context')
                    ->info('Default deserialization context')
                    ->variablePrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('symfony_serializer')
                    ->info('Configuration for Symfony serializer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('normalizers')
                            ->info('Custom normalizers to register')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('denormalizers')
                            ->info('Custom denormalizers to register')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('encoders')
                            ->info('Custom encoders to register')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('vuryss_serializer')
                    ->info('Configuration for Vuryss serializer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('normalizers')
                            ->info('Custom normalizers to register')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('denormalizers')
                            ->info('Custom denormalizers to register')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->booleanNode('enable_cache')
                            ->info('Enable metadata caching for better performance')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
