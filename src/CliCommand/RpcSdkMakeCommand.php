<?php

namespace Ufo\JsonRpcSdkBundle\CliCommand;

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Ufo\RpcSdk\Maker\Definitions\Configs\ConfigsHolder;
use Ufo\RpcSdk\Maker\DocReader\FileReader;
use Ufo\RpcSdk\Maker\DocReader\HttpReader;
use Ufo\RpcSdk\Maker\Interfaces\IClassLikeDefinition;
use Ufo\RpcSdk\Maker\Interfaces\IHaveMethodsDefinitions;
use Ufo\RpcSdk\Maker\Maker;
use Ufo\RpcSdk\Maker\SdkConfigMaker;
use Ufo\RpcSdk\Maker\SdkDtoMaker;
use Ufo\RpcSdk\Maker\SdkEnumMaker;
use Ufo\RpcSdk\Maker\SdkProcedureMaker;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\KernelInterface;

use function count;
use function str_starts_with;
use function trim;

#[AsCommand(
    name: RpcSdkMakeCommand::COMMAND_NAME,
    description: 'Make SDK classes for RPC procedures',
)]
class RpcSdkMakeCommand extends Command
{
    const string COMMAND_NAME = 'ufo:sdk:make';

    protected ContainerInterface $container;
    protected SymfonyStyle $io;

    public function __construct(protected KernelInterface $kernel)
    {
        $this->container = $kernel->getContainer();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('vendor', InputArgument::REQUIRED, 'Vendor name for SDK namespace')
             ->addArgument('api_doc', InputArgument::REQUIRED, 'API URL or local file path for RPC JSON documentation')
             ->addOption('token_name', 't', InputOption::VALUE_OPTIONAL, 'Security token key in header')
             ->addOption('token', 's', InputOption::VALUE_OPTIONAL, 'Security token value');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $vendorName = trim($input->getArgument('vendor'));
            $apiDoc = trim($input->getArgument('api_doc'));
            $headers = [];
            try {
                $tokenKey = trim($input->getOption('token_name'));
                $token = trim($input->getOption('token'));
                $headers = (!empty($token) && !empty($tokenKey)) ? [$tokenKey => $token] : [];
            } catch (InvalidArgumentException) {
            }

            $docReader = str_starts_with($apiDoc, 'http')
                ? new HttpReader($apiDoc, $headers)
                : new FileReader($apiDoc);

            $configHolder = new ConfigsHolder(
                $docReader,
                projectRootDir: $this->container->getParameter('kernel.project_dir'),
                apiVendorAlias: $vendorName,
                namespace: $this->getRootNamespace(),
                urlInAttr: $this->getUrlInSdk(),
                cacheLifeTimeSecond: $this->getCacheTTL(),
                cache: $this->container->get('cache.app')
            );
            $generator = new Generator(
                new FileManager(
                    new Filesystem(),
                    new AutoloaderUtil(
                        new ComposerAutoloaderFinder($configHolder->namespace)
                    ),
                    new MakerFileLinkFormatter(),
                    $configHolder->projectRootDir
                ),
                $configHolder->namespace
            );

            $maker = new Maker (
                configsHolder: $configHolder,
                generator: $generator,
                makers: [
                    new SdkEnumMaker($configHolder, $generator),
                    new SdkDtoMaker($configHolder, $generator),
                    new SdkProcedureMaker($configHolder, $generator),
                    new SdkConfigMaker($configHolder, $generator),
                ]
            );

            $this->io->section("<fg=bright-magenta>SDK for '$vendorName' ($apiDoc):</>");

            $maker->make(function (IClassLikeDefinition $classDefinition): IClassLikeDefinition {
                $this->printResult($classDefinition);
                return $classDefinition;
            });

            $this->io->title("<fg=cyan>SDK for $vendorName ($apiDoc) complete</>");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->io->getErrorStyle()->error([
                $e->getMessage(),
                $e->getFile() . ': ' . $e->getLine(),
            ]);
            return Command::FAILURE;
        }
    }

    protected function getRootNamespace(): string
    {
        if ($this->container->hasParameter('json_rpc_sdk.namespace')) {
            $namespace = $this->container->getParameter('json_rpc_sdk.namespace');
        } else {
            $ref = new \ReflectionObject($this->kernel);
            $namespace= $ref->getNamespaceName() . '\Sdk';
        }
        return $namespace;
    }

    protected function getUrlInSdk(): bool
    {
        $inSdk = false;
        if ($this->container->hasParameter('json_rpc_sdk.generate_url_in_attr')) {
            $inSdk = $this->container->getParameter('json_rpc_sdk.generate_url_in_attr');
        }
        return $inSdk;
    }

    protected function getCacheTTL(): int
    {
        $ttl = ConfigsHolder::DEFAULT_CACHE_LIFETIME;
        if ($this->container->hasParameter('json_rpc_sdk.cache.ttl')) {
            $ttl = $this->container->getParameter('json_rpc_sdk.cache.ttl');
        }
        return $ttl;
    }

    protected function printResult(IClassLikeDefinition $classDefinition): void
    {
        $this->io->writeln('Class: <question>' . $classDefinition->getFQCN() . '</>');
        if (count($classDefinition->getProperties()) > 0) {
            foreach ($classDefinition->getProperties() as $name => $property) {
                $this->io->writeln('<info>public ' . $property . ' $' . $name . '</info>');
            }
        }
        if ($classDefinition instanceof IHaveMethodsDefinitions) {
            $methodsInfo = [];
            foreach ($classDefinition->getMethods() as $method) {
                $methodsInfo[] = '<comment>' . $method->getName() . '</comment>' .
                                 '<info>(' . $method->getArgumentsSignature() . ')</info>' .
                                 '<comment>' . (!empty($method->getReturns()) ? ':' : '') .
                                 $method->getReturns() . '</>';
            }
            $this->io->listing($methodsInfo);
        }
    }
}
