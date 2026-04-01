<?php

namespace App\Account\Infrastructure\Console;

use App\Account\Application\Handler\GetAccountBalanceHandler;
use App\Account\Application\Query\GetAccountBalanceQuery;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:get-account-balance',
    description: 'Get account balance via CQRS query',
)]
class GetAccountBalanceConsoleCommand extends Command
{
    public function __construct(
        private readonly GetAccountBalanceHandler $getAccountBalanceHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('accountId', InputArgument::REQUIRED, 'Account ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accountId = $input->getArgument('accountId');

        $query = new GetAccountBalanceQuery($accountId);

        try {
            $response = $this->getAccountBalanceHandler->handle($query);

            if (!$response) {
                $io->error("Account with ID {$accountId} not found");

                return Command::FAILURE;
            }

            $io->success('Account Balance Information:');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Account ID', $response->accountId],
                    ['Balance', $response->balance],
                    ['Currency', $response->currency],
                    ['Last Updated', $response->lastUpdated->format('Y-m-d H:i:s')],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error getting account balance: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
