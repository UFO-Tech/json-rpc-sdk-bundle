<?php

namespace Ufo\JsonRpcSdkBundle\DependencyInjection;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Ufo\RpcSdk\Interfaces\ISdkMethodClass;

class SdkCompiler implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        // Витягуємо значення з конфігурації
        $vendors = [];
        $ns = $container->getParameter(Configuration::TREE_BUILDER_NAME . '.namespace');
        foreach ($container->getParameter(Configuration::TREE_BUILDER_NAME . '.vendors') as $vendorData) {
            $vendors[$ns . '\\' . Str::asCamelCase($vendorData['name'])] = $vendorData;
        }
        $services = $container->findTaggedServiceIds('ufo.sdk_method_class');
        foreach ($services as $id => $service) {
            try {
                $definition = $container->findDefinition($id);
                $ref = new \ReflectionClass($definition->getClass());
                $vendorData = $vendors[$ref->getNamespaceName()];
                if (isset($vendorData['token'])
                    && isset($vendorData['token_key'])
                ) {
                    $header = [
                        $vendors[$ref->getNamespaceName()]['token_key'] => $vendors[$ref->getNamespaceName()]['token'],
                    ];
                    $definition->setArgument('$headers', $header);
                }
            } catch (\Throwable) {
                continue;
            }
        }

    }
}
