<?php

namespace Ufo\JsonRpcSdkBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Ufo\JsonRpcSdkBundle\DependencyInjection\SdkCompiler;

class JsonRpcSdkBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SdkCompiler());
    }
}
