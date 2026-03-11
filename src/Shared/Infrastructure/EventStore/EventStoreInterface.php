<?php

namespace App\Shared\Infrastructure\EventStore;

interface EventStoreInterface
{
    public function saveEvents(string $aggregateId, array $events, int $expectedVersion): void;

    public function getEventsForAggregate(string $aggregateId): array;

    public function getEventsForAggregateFromVersion(string $aggregateId, int $version): array;

    public function getAllEvents(): array;

    public function getEventsByType(string $eventType): array;
}
