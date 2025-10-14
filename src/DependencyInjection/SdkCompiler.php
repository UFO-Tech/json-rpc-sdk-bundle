<?php

namespace Ufo\JsonRpcSdkBundle\DependencyInjection;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Throwable;
use Ufo\RpcSdk\Interfaces\ISdkMethodClass;
use Ufo\RpcSdk\Procedures\ResponseTransformer\Interfaces\IResponseHandler;

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
        $this->processSyncMethods($container, $vendors);
        $this->processAsyncMethods($container, $vendors);
    }

    protected function processSyncMethods(ContainerBuilder $container, array $vendors): void
    {
        $services = $container->findTaggedServiceIds(ISdkMethodClass::TAG);
        $responseHandlers = array_map(
            fn (string $id) => $container->findDefinition($id),
            array_keys($container->findTaggedServiceIds(IResponseHandler::TAG))
        );
        foreach ($services as $id => $service) {
            try {
                $definition = $container->findDefinition($id);
                $ref = new \ReflectionClass($definition->getClass());

                $vendorData = $this->getVendors($vendors, $ref);

                if (isset($vendorData['token'])
                    && isset($vendorData['token_key'])
                ) {
                    $header = [
                        $vendors[$ref->getNamespaceName()]['token_key'] => $vendors[$ref->getNamespaceName()]['token'],
                    ];
                    $definition->setArgument('$headers', $header);
                }
                $definition->setArgument('$handlers', $responseHandlers);
            } catch (\Throwable) {
                continue;
            }
        }
    }
    protected function processAsyncMethods(ContainerBuilder $container, array $vendors): void
    {
        $services = $container->findTaggedServiceIds(ISdkMethodClass::ASYNC_TAG);
        foreach ($services as $id => $service) {
            try {
                $definition = $container->findDefinition($id);
                $ref = new \ReflectionClass($definition->getClass());

                $vendorData = $this->getVendors($vendors, $ref);

                if (isset($vendorData['token'])) {
                    $definition->setArgument('$token', $vendors[$ref->getNamespaceName()]['token']);
                }
                if (isset($vendorData['async_secret'])) {
                    $definition->setArgument('$secretAsync', $vendors[$ref->getNamespaceName()]['async_secret']);
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }

    protected function getVendors(array &$vendors, \ReflectionClass $ref): array
    {
        $namespace = $ref->getNamespaceName();

        if (!isset($vendors[$namespace])) {
            $parentNamespace = $ref->getParentClass()?->getNamespaceName();
            if ($parentNamespace && isset($vendors[$parentNamespace])) {
                $vendors[$namespace] = $vendors[$parentNamespace];
                $namespace = $parentNamespace;
            }
        }

        return $vendors[$namespace];
    }
}
