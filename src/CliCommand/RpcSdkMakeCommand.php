<?php

namespace Ufo\JsonRpcSdkBundle\CliCommand;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Ufo\RpcSdk\Maker\Definitions\ClassDefinition;
use Ufo\RpcSdk\Maker\Maker;
use UfoCms\ColoredCli\CliColor;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: RpcSdkMakeCommand::COMMAND_NAME,
    description: 'Make SDK classes for RPC procedures',
)]
class RpcSdkMakeCommand extends Command
{
    const COMMAND_NAME = 'ufo:sdk:make';

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
            ->addArgument('api_url', InputArgument::REQUIRED, 'API url for get rpc json documentation')
            ->addOption('token_name', 't', InputOption::VALUE_OPTIONAL, 'Security token key in header')
            ->addOption('token', 's', InputOption::VALUE_OPTIONAL, 'Security token value');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $vendorName = trim($input->getArgument('vendor'));
            $apiUrl = trim($input->getArgument('api_url'));
            $headers = [];
            try {
                $tokenKey = trim($input->getOption('token_name'));
                $token = trim($input->getOption('token'));
                $headers = (!empty($token) && !empty($tokenKey)) ? [$tokenKey => $token] : [];
            } catch (InvalidArgumentException) {
            }

            $maker = new Maker(
                apiUrl: $apiUrl,
                apiVendorAlias: $vendorName,
                headers: $headers,
                namespace: $this->getRootNamespace(),
                projectRootDir: $this->container->getParameter('kernel.project_dir'),
                cacheLifeTimeSecond: Maker::DEFAULT_CACHE_LIFETIME,
                cache: $this->container->get('cache.app'),
                urlInAttr: $this->getUrlInSdk()
            );

            $this->io->section("<fg=bright-magenta>SDK for '$vendorName' ($apiUrl):</>");

            $maker->make(function (ClassDefinition $classDefinition) {
                $this->printResult($classDefinition);
            });

            $this->io->title("<fg=cyan>SDK for $vendorName ($apiUrl) complete</>");

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

    protected function printResult(ClassDefinition $classDefinition): void
    {
        $this->io->writeln('Class: <question>' . $classDefinition->getFullName() . '</>');
        $this->io->writeln('Methods: ');

        $methhodsInfo = [];
        foreach ($classDefinition->getMethods() as $method) {
            $methhodsInfo[] = '<comment>' . $method->getName() . '</comment>' .
                '<info>(' . $method->getArgumentsSignature() . ')</info>' .
                '<comment>' . (!empty($method->getReturns()) ? ':' : '') .
                implode('|', $method->getReturns()) . '</>';
        }

        $this->io->listing($methhodsInfo);
    }
}
