<?php

namespace App\Command;

use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Application\Handler\EventSourcedCreateAccountHandler;
use App\Account\Application\Handler\EventSourcedDepositMoneyHandler;
use App\Account\Application\Handler\EventSourcedGetAccountBalanceHandler;
use App\Account\Application\Handler\EventSourcedWithdrawMoneyHandler;
use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use App\Shared\Infrastructure\EventStore\EventStoreInterface;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Handler\EventSourcedCreateUserHandler;
use App\User\Domain\ValueObject\UserRole;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-event-sourcing',
    description: 'Test Event Sourcing implementation',
)]
class TestEventSourcingCommand extends Command
{
    public function __construct(
        private EventSourcedCreateUserHandler $createUserHandler,
        private EventSourcedCreateAccountHandler $createAccountHandler,
        private EventSourcedDepositMoneyHandler $depositMoneyHandler,
        private EventSourcedWithdrawMoneyHandler $withdrawMoneyHandler,
        private EventSourcedGetAccountBalanceHandler $getBalanceHandler,
        private EventStoreInterface $eventStore,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Event Sourcing Implementation');

        try {
            // 1. Create User
            $io->section('Creating User');
            $createUserCommand = new CreateUserCommand(
                'test@example.com',
                'password123',
                UserRole::USER
            );
            $userId = $this->createUserHandler->handle($createUserCommand);
            $io->success("User created with ID: $userId");

            // 2. Create Account
            $io->section('Creating Account');
            $createAccountCommand = new CreateAccountCommand($userId, Currency::UAH);
            $accountId = $this->createAccountHandler->handle($createAccountCommand);
            $io->success("Account created with ID: $accountId");

            // 3. Check initial balance
            $io->section('Checking Initial Balance');
            $balanceQuery = new GetAccountBalanceQuery($accountId);
            $balance = $this->getBalanceHandler->handle($balanceQuery);
            $io->info("Initial balance: {$balance->balance} {$balance->currency}");

            // 4. Deposit money
            $io->section('Depositing Money');
            $depositCommand = new DepositMoneyCommand($accountId, new Money('100.50', Currency::UAH));
            $this->depositMoneyHandler->handle($depositCommand);
            $io->success('Money deposited successfully');

            // 5. Check balance after deposit
            $balance = $this->getBalanceHandler->handle($balanceQuery);
            $io->info("Balance after deposit: {$balance->balance} {$balance->currency}");

            // 6. Withdraw money
            $io->section('Withdrawing Money');
            $withdrawCommand = new WithdrawMoneyCommand($accountId, new Money('30.25', Currency::UAH));
            $this->withdrawMoneyHandler->handle($withdrawCommand);
            $io->success('Money withdrawn successfully');

            // 7. Check final balance
            $balance = $this->getBalanceHandler->handle($balanceQuery);
            $io->info("Final balance: {$balance->balance} {$balance->currency}");

            // 8. Show all events
            $io->section('Event Store Contents');
            $events = $this->eventStore->getEventsForAggregate($accountId);
            $io->table(
                ['Event Type', 'Data', 'Occurred At'],
                array_map(function ($event) {
                    return [
                        $event->getEventType(),
                        json_encode($event->getEventData()),
                        $event->getOccurredAt()->format('Y-m-d H:i:s'),
                    ];
                }, $events)
            );

            $io->success('Event Sourcing test completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
