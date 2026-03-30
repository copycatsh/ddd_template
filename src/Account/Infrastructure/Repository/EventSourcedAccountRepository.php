<?php

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Shared\Infrastructure\EventStore\EventStoreInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;

class EventSourcedAccountRepository implements EventSourcedAccountRepositoryInterface
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private MessageBusInterface $messageBus,
        private Connection $connection,
    ) {
    }

    /**
     * Save aggregate events to the event store and dispatch them via Messenger.
     *
     * Wraps everything in a DBAL transaction so event store writes and projection
     * updates (triggered by sync Messenger dispatch) are atomic. DoctrineEventStore
     * uses a nested transaction (Doctrine savepoints) inside this outer transaction.
     *
     * TODO: For distributed systems, replace sync dispatch with Outbox Pattern:
     * store events in an outbox table (same transaction), then publish asynchronously
     * via a background worker. See: https://microservices.io/patterns/data/transactional-outbox.html
     */
    public function save(EventSourcedAccount $account): void
    {
        $events = $account->getUncommittedEvents();

        if (empty($events)) {
            return;
        }

        $expectedVersion = $account->getVersion() - count($events);

        $this->connection->beginTransaction();

        try {
            $this->eventStore->saveEvents(
                $account->getId(),
                $events,
                $expectedVersion
            );

            foreach ($events as $event) {
                $this->messageBus->dispatch($event);
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $account->markEventsAsCommitted();
    }

    public function findById(string $id): ?EventSourcedAccount
    {
        $events = $this->eventStore->getEventsForAggregate($id);

        if (empty($events)) {
            return null;
        }

        return EventSourcedAccount::reconstitute($id, $events);
    }
}
