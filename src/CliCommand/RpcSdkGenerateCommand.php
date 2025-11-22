<?php

namespace Ufo\JsonRpcSdkBundle\CliCommand;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use function implode;

#[AsCommand(
    name: RpcSdkGenerateCommand::COMMAND_NAME,
    description: 'Generate SDK classes for RPC procedures from configs',
)]
class RpcSdkGenerateCommand extends Command
{
    const string COMMAND_NAME = 'ufo:sdk:generate';

    protected ContainerInterface $container;

    public function __construct(protected KernelInterface $kernel)
    {
        $this->container = $kernel->getContainer();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('vendor', null, InputOption::VALUE_OPTIONAL, 'Optional vendor name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $vendorName = $input->getOption('vendor');
        $vendors = $this->getVendors($vendorName);

        $io = new SymfonyStyle($input, $output);
        try {
            $io->title("<options=bold>Start generate SDK from configs</>");

            foreach ($vendors as $vendorData) {
                $command = $this->getApplication()->get('ufo:sdk:make');
                $childInput = new ArrayInput([
                    'vendor' => $vendorData['name'],
                    'api_doc' => $vendorData['url'],
                    '-t' => ($vendorData['token_key'] ?? ''),
                    '-s' => ($vendorData['token'] ?? ''),
                    '-i' => implode(',', $vendorData['ignore_methods'] ?? ''),
                ]);
                $command->run($childInput, $output);
            }
            $io->success('Generate SDK is complete');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->getErrorStyle()->error([
                $e->getMessage(),
                $e->getFile() . ': ' . $e->getLine(),
            ]);
            return Command::FAILURE;
        }
    }

    protected function getVendors(?string $vendorName): array
    {
        $vendors = [];
        if ($this->container->hasParameter('json_rpc_sdk.vendors')) {
            $vendors = $this->container->getParameter('json_rpc_sdk.vendors');
        }

        if ($vendorName !== null) {
            foreach ($vendors as $vendor) {
                if ($vendor['name'] === $vendorName) {
                    return [$vendor];
                }
            }
            return [];
        }

        return $vendors;
    }
}
