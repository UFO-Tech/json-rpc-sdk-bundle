<?php

namespace Ufo\JsonRpcSdkBundle;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Ufo\JsonRpcSdkBundle\DependencyInjection\SdkCompiler;
use Ufo\RpcObject\Transformer\DTOTransformerBundleBootTrait;

class JsonRpcSdkBundle extends Bundle
{
    use DTOTransformerBundleBootTrait;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SdkCompiler());
    }

    public function boot(): void
    {
        $this->bootDTOTransformers();
        parent::boot();
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
