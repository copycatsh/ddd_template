<?php

namespace App\Shared\Domain\Event;

abstract class AbstractDomainEvent implements DomainEventInterface
{
    private \DateTimeImmutable $occurredAt;
    private int $version;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
        $this->version = 1;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getEventType(): string
    {
        return static::class;
    }

    abstract public function getAggregateId(): string;

    abstract public function getEventData(): array;
}
