<?php

namespace Ufo\JsonRpcSdkBundle\CliCommand;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Ufo\RpcSdk\Maker\Definitions\ClassDefinition;
use Ufo\RpcSdk\Maker\Maker;
use UfoCms\ColoredCli\CliColor;
use Symfony\Component\Console\Exception\InvalidArgumentException;


#[AsCommand(
    name: UfoRpcSdkGenerateCommand::COMMAND_NAME,
    description: 'Handle async rpc request',
)]
class UfoRpcSdkGenerateCommand extends Command
{
    const COMMAND_NAME = 'ufo:sdk:make';


    public function __construct()
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->addArgument('vendor', InputArgument::REQUIRED, 'Vendor name for SDK namespace')
            ->addArgument('api_url', InputArgument::REQUIRED, 'API url for get rpc json documentation')
            ->addOption('token_name', 'n', InputOption::VALUE_OPTIONAL, 'Security token key in header')
            ->addOption('token', 't', InputOption::VALUE_OPTIONAL, 'Security token value');
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
                $headers = [$tokenKey => $token];
            } catch (InvalidArgumentException) {
            }

            $this->getApplication()->getNamespaces();
            $maker = new Maker(
                apiUrl: $apiUrl,
                apiVendorAlias: $vendorName,
                headers: $headers,
                namespace: "App\Sdk", // 'Ufo\RpcSdk\Client'
                cacheLifeTimeSecond: Maker::DEFAULT_CACHE_LIFETIME // 3600
            );

            echo CliColor::GREEN->value . "Start generate SDK for '$vendorName' ($apiUrl)" . CliColor::RESET->value . PHP_EOL;

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

            echo CliColor::GREEN->value . "Generate SDK is complete" . CliColor::RESET->value . PHP_EOL;

            $result = '';
            $io->writeln($result);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->getErrorStyle()->error([
                $e->getMessage(),
                $e->getFile() . ': ' . $e->getLine(),
            ]);
            return Command::FAILURE;
        }
    }
}
