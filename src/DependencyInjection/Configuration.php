<?php

namespace Ufo\JsonRpcSdkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Ufo\JsonRpcBundle\ConfigService\RpcSecurityConfig;

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
    const string TREE_BUILDER_NAME = 'json_rpc_sdk';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::TREE_BUILDER_NAME);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('namespace')->defaultValue('App\Sdk')->end()
            ->booleanNode('generate_url_in_attr')->defaultValue(false)->end()
            ->arrayNode('vendors')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('name')->isRequired()->end()
                        ->scalarNode('url')->isRequired()->end()
                        ->scalarNode('token_key')->end()
                        ->scalarNode('token')->end()
                        ->scalarNode('async_secret')->end()
                    ->end()
                ->end()
            ->end()
        ->end();
        return $treeBuilder;
    }
}
