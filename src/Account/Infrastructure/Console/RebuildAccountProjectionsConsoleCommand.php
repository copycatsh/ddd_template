<?php

namespace App\Account\Infrastructure\Console;

use App\Account\Domain\Event\AccountCreatedEvent;
use App\Account\Domain\Event\MoneyDepositedEvent;
use App\Account\Domain\Event\MoneyWithdrawnEvent;
use App\Account\Infrastructure\Projection\AccountProjectionHandler;
use App\Shared\Infrastructure\EventStore\EventStoreInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rebuild-account-projections',
    description: 'Rebuild account projections from event store',
)]
class RebuildAccountProjectionsConsoleCommand extends Command
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly AccountProjectionHandler $projectionHandler,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Replaying events from event store...');
        $allEvents = $this->eventStore->getAllEvents();

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement('DELETE FROM account_projections');

            $projected = 0;
            foreach ($allEvents as $event) {
                $handled = match (get_class($event)) {
                    AccountCreatedEvent::class => $this->projectionHandler->onAccountCreated($event),
                    MoneyDepositedEvent::class => $this->projectionHandler->onMoneyDeposited($event),
                    MoneyWithdrawnEvent::class => $this->projectionHandler->onMoneyWithdrawn($event),
                    default => false,
                };

                if (false !== $handled) {
                    ++$projected;
                }
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $io->error(sprintf('Rebuild failed: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM account_projections');
        $io->success("Rebuilt {$count} account projections from {$projected} events.");

        return Command::SUCCESS;
    }
}
