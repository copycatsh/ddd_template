<?php

namespace App\Command;

use App\Account\Application\Handler\GetUserAccountsHandler;
use App\Account\Application\Query\GetUserAccountsQuery;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:get-user-accounts',
    description: 'Get user accounts via CQRS query',
)]
class GetUserAccountsConsoleCommand extends Command
{
    public function __construct(
        private readonly GetUserAccountsHandler $getUserAccountsHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'User ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getArgument('userId');

        $query = new GetUserAccountsQuery($userId);

        try {
            $response = $this->getUserAccountsHandler->handle($query);

            if (empty($response->accounts)) {
                $io->warning("No accounts found for user {$userId}");

                return Command::SUCCESS;
            }

            $io->success('User Accounts Information:');
            $io->writeln("User ID: {$response->userId}");
            $io->writeln('Number of accounts: '.count($response->accounts));

            $tableRows = [];
            foreach ($response->accounts as $account) {
                $tableRows[] = [
                    $account->accountId,
                    $account->balance,
                    $account->currency,
                    $account->createdAt->format('Y-m-d H:i:s'),
                ];
            }

            $io->table(
                ['Account ID', 'Balance', 'Currency', 'Created At'],
                $tableRows
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error getting user accounts: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
