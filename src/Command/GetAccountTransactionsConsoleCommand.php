<?php

namespace App\Command;

use App\Account\Application\Handler\GetAccountTransactionsHandler;
use App\Account\Application\Query\GetAccountTransactionsQuery;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:account:transactions',
    description: 'View transaction history for an account'
)]
class GetAccountTransactionsConsoleCommand extends Command
{
    public function __construct(
        private readonly GetAccountTransactionsHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('accountId', InputArgument::REQUIRED, 'Account ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $accountId = $input->getArgument('accountId');

        try {
            $query = new GetAccountTransactionsQuery($accountId);
            $transactions = $this->handler->handle($query);

            if (empty($transactions)) {
                $io->info("No transactions found for account: {$accountId}");

                return Command::SUCCESS;
            }

            $io->title("Transaction History for Account: {$accountId}");
            $io->text('Total transactions: '.count($transactions));

            $tableData = array_map(function ($transaction) use ($accountId) {
                // Determine direction: OUT if from this account, IN if to this account
                $direction = $transaction->fromAccountId === $accountId ? '→ OUT' : '← IN';

                // Show the other account ID
                $otherAccount = $transaction->fromAccountId === $accountId
                    ? ($transaction->toAccountId ?? 'N/A')
                    : $transaction->fromAccountId;

                return [
                    substr($transaction->id, 0, 8).'...',
                    $transaction->type,
                    $direction,
                    substr($otherAccount, 0, 20).'...',
                    $transaction->amount.' '.$transaction->currency,
                    $transaction->status,
                    $transaction->createdAt,
                ];
            }, $transactions);

            $io->table(
                ['ID', 'Type', 'Direction', 'Counterparty', 'Amount', 'Status', 'Created At'],
                $tableData
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to fetch transactions: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
