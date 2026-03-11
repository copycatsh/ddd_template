<?php

namespace App\Shared\Domain\Event;

interface DomainEventInterface
{
    public function getAggregateId(): string;

    public function getEventType(): string;

    public function getOccurredAt(): \DateTimeImmutable;

    public function getEventData(): array;

    public function getVersion(): int;
}
