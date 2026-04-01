<?php

namespace App\Account\Infrastructure\Console;

use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Application\Handler\WithdrawMoneyHandler;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:withdraw-money',
    description: 'Withdraw money from account via CQRS command',
)]
class WithdrawMoneyConsoleCommand extends Command
{
    public function __construct(
        private readonly WithdrawMoneyHandler $withdrawMoneyHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('accountId', InputArgument::REQUIRED, 'Account ID')
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount to withdraw')
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
        $command = new WithdrawMoneyCommand($accountId, $money);

        try {
            $this->withdrawMoneyHandler->handle($command);
            $io->success("Successfully withdrew {$amount} {$currency->value} from account {$accountId}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error withdrawing money: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
