<?php

namespace Ufo\JsonRpcSdkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Default TreeBuilder name
     */
    const TREE_BUILDER_NAME = 'ufo_json_rpc_sdk';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::TREE_BUILDER_NAME);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('vendors')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('name')->isRequired()->end()
                        ->scalarNode('url')->isRequired()->validate()->url()->end()
                        ->scalarNode('token_key')->end()
                        ->scalarNode('token')->end()
                    ->end()
                ->end()
            ->end()
        ->end()
    ;
        return $treeBuilder;
    }
}
