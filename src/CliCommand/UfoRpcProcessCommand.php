<?php

namespace Ufo\JsonRpcBundle\CliCommand;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;



#[AsCommand(
    name: UfoRpcSdkGenerateCommand::COMMAND_NAME,
    description: 'Handle async rpc request',
)]
class UfoRpcSdkGenerateCommand extends Command
{
    const COMMAND_NAME = 'ufo:rpc:sdk-generate';


    public function __construct()
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->addArgument('vendor', InputArgument::REQUIRED, 'Vendor name for SDK namespace')
            ->addArgument('api_url', InputArgument::REQUIRED, 'API url for get rpc json documentation')
            ->addOption('token', 't', InputOption::VALUE_REQUIRED, 'Security token')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {

            $vendorName = trim($input->getArgument('vendor'), '"');
            $apiUrl = trim($input->getArgument('api_url'), '"');
            $token = trim($input->getOption('token'), '"');

            $result = '';
            $io->writeln($result);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->getErrorStyle()->error([
                $e->getMessage(),
                $e->getFile() . ': ' . $e->getLine()
            ]);
            return Command::FAILURE;
        }
    }
}
