<?php

namespace App\Command;

use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Application\Handler\DepositMoneyHandler;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deposit-money',
    description: 'Deposit money to account via CQRS command',
)]
class DepositMoneyConsoleCommand extends Command
{
    public function __construct(
        private readonly DepositMoneyHandler $depositMoneyHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('accountId', InputArgument::REQUIRED, 'Account ID')
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount to deposit')
            ->addArgument('currency', InputArgument::REQUIRED, 'Currency (UAH or USD)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accountId = $input->getArgument('accountId');
        $amount = $input->getArgument('amount');
        $currencyString = $input->getArgument('currency');

        try {
            $currency = Currency::from(strtoupper($currencyString));
        } catch (\ValueError $e) {
            $io->error('Invalid currency. Use UAH or USD.');

            return Command::FAILURE;
        }

        $money = new Money($amount, $currency);
        $command = new DepositMoneyCommand($accountId, $money);

        try {
            $this->depositMoneyHandler->handle($command);
            $io->success("Successfully deposited {$amount} {$currency->value} to account {$accountId}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error depositing money: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
