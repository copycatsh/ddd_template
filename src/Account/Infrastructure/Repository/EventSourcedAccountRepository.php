<?php

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Shared\Infrastructure\EventStore\EventStoreInterface;

class EventSourcedAccountRepository implements EventSourcedAccountRepositoryInterface
{
    public function __construct(
        private EventStoreInterface $eventStore,
    ) {
    }

    public function save(EventSourcedAccount $account): void
    {
        $events = $account->getUncommittedEvents();

        if (empty($events)) {
            return;
        }

        $expectedVersion = $account->getVersion() - count($events);

        $this->eventStore->saveEvents(
            $account->getId(),
            $events,
            $expectedVersion
        );

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

    public function findByUserIdAndCurrency(string $userId, Currency $currency): ?EventSourcedAccount
    {
        // For now, we'll implement a simple scan - in production, you'd want indexing
        $allEvents = $this->eventStore->getAllEvents();

        foreach ($allEvents as $event) {
            if ($event instanceof \App\Account\Domain\Event\AccountCreatedEvent) {
                if ($event->getUserId() === $userId && $event->getCurrency()->equals($currency)) {
                    return $this->findById($event->getAccountId());
                }
            }
        }

        return null;
    }

    public function findByUserId(string $userId): array
    {
        // For now, we'll implement a simple scan - in production, you'd want indexing
        $allEvents = $this->eventStore->getAllEvents();
        $accountIds = [];

        foreach ($allEvents as $event) {
            if ($event instanceof \App\Account\Domain\Event\AccountCreatedEvent) {
                if ($event->getUserId() === $userId) {
                    $accountIds[] = $event->getAccountId();
                }
            }
        }

        $accounts = [];
        foreach ($accountIds as $accountId) {
            $account = $this->findById($accountId);
            if ($account) {
                $accounts[] = $account;
            }
        }

        return $accounts;
    }
}
