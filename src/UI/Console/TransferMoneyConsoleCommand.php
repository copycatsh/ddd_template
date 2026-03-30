<?php

namespace App\UI\Console;

use App\Account\Application\Saga\TransferMoneySaga;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:transfer-money',
    description: 'Transfer money between two accounts'
)]
class TransferMoneyConsoleCommand extends Command
{
    public function __construct(
        private readonly TransferMoneySaga $saga,
        private readonly EventSourcedAccountRepositoryInterface $accountRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('fromAccountId', InputArgument::REQUIRED, 'Source account ID')
            ->addArgument('toAccountId', InputArgument::REQUIRED, 'Destination account ID')
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount to transfer')
            ->addArgument('currency', InputArgument::REQUIRED, 'Currency (UAH or USD)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fromAccountId = $input->getArgument('fromAccountId');
        $toAccountId = $input->getArgument('toAccountId');
        $amount = $input->getArgument('amount');
        $currency = $input->getArgument('currency');

        try {
            $fromAccount = $this->accountRepository->findById($fromAccountId);
            $toAccount = $this->accountRepository->findById($toAccountId);

            if (!$fromAccount || !$toAccount) {
                $io->error('One or both accounts not found');

                return Command::FAILURE;
            }

            $io->section('Before Transfer:');
            $io->table(
                ['Account', 'Balance', 'Currency'],
                [
                    ['From', $fromAccount->getBalance()->getAmount(), $fromAccount->getCurrency()->value],
                    ['To', $toAccount->getBalance()->getAmount(), $toAccount->getCurrency()->value],
                ]
            );

            $transactionId = $this->saga->execute(
                $fromAccountId,
                $toAccountId,
                new Money($amount, Currency::from($currency))
            );

            $fromAccount = $this->accountRepository->findById($fromAccountId);
            $toAccount = $this->accountRepository->findById($toAccountId);

            $io->section('After Transfer:');
            $io->table(
                ['Account', 'Balance', 'Currency'],
                [
                    ['From', $fromAccount->getBalance()->getAmount(), $fromAccount->getCurrency()->value],
                    ['To', $toAccount->getBalance()->getAmount(), $toAccount->getCurrency()->value],
                ]
            );

            $io->success("Transfer completed! Transaction ID: {$transactionId}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Transfer failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
