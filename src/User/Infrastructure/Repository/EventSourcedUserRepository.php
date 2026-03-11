<?php

namespace App\User\Infrastructure\Repository;

use App\Shared\Infrastructure\EventStore\EventStoreInterface;
use App\User\Domain\Entity\EventSourcedUser;
use App\User\Domain\Repository\EventSourcedUserRepositoryInterface;

class EventSourcedUserRepository implements EventSourcedUserRepositoryInterface
{
    public function __construct(
        private EventStoreInterface $eventStore,
    ) {
    }

    public function save(EventSourcedUser $user): void
    {
        $events = $user->getUncommittedEvents();

        if (empty($events)) {
            return;
        }

        $expectedVersion = $user->getVersion() - count($events);

        $this->eventStore->saveEvents(
            $user->getId(),
            $events,
            $expectedVersion
        );

        $user->markEventsAsCommitted();
    }

    public function findById(string $id): ?EventSourcedUser
    {
        $events = $this->eventStore->getEventsForAggregate($id);

        if (empty($events)) {
            return null;
        }

        return EventSourcedUser::reconstitute($id, $events);
    }

    public function findByEmail(string $email): ?EventSourcedUser
    {
        // For now, we'll implement a simple scan - in production, you'd want indexing
        $allEvents = $this->eventStore->getAllEvents();

        foreach ($allEvents as $event) {
            if ($event instanceof \App\User\Domain\Event\UserCreatedEvent) {
                if ($event->getEmail() === $email) {
                    return $this->findById($event->getUserId());
                }
            }
        }

        return null;
    }
}
