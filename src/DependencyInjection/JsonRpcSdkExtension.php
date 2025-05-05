<?php

namespace Ufo\JsonRpcSdkBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class JsonRpcSdkExtension extends Extension
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->container = $container;
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->container->setParameter(Configuration::TREE_BUILDER_NAME, $config);

        $this->mapTreeToParams($config, Configuration::TREE_BUILDER_NAME);

        $loader = new Loader\YamlFileLoader($this->container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    protected function mapTreeToParams(array $paramsArray, string $paramKey)
    {
        foreach ($paramsArray as $key => $value) {
            $newKey = $paramKey . '.' . $key;
            $this->container->setParameter($newKey, $value);
            if (is_array($value)) {
                $this->mapTreeToParams($value, $newKey);
            }
        }
    }

    public function getAlias(): string
    {
        return Configuration::TREE_BUILDER_NAME;
    }

}
