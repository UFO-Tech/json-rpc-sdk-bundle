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
        $io = new SymfonyStyle($input, $output);
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
                cacheLifeTimeSecond: Maker::DEFAULT_CACHE_LIFETIME // 3600
            );

            $io->writeln("Start make SDK for '$vendorName' ($apiUrl)");

            $maker->make(function (ClassDefinition $classDefinition) use ($io) {

                echo 'Create class: ' . CliColor::LIGHT_BLUE->value . $classDefinition->getFullName() . CliColor::RESET->value . PHP_EOL;
                echo 'Methods: ' . PHP_EOL;
                foreach ($classDefinition->getMethods() as $method) {
                    echo CliColor::CYAN->value .
                        $method->getName() .
                        '(' . $method->getArgumentsSignature() . ')' .
                        (!empty($method->getReturns()) ? ':' : '') .
                        implode('|', $method->getReturns()) .
                        CliColor::RESET->value . PHP_EOL;
                }
                echo str_repeat('=', 20) . PHP_EOL;
            });

            $io->writeln('Make SDK is complete');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->getErrorStyle()->error([
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
}
