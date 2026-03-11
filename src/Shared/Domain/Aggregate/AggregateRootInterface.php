<?php

namespace App\Shared\Domain\Aggregate;

use App\Shared\Domain\Event\DomainEventInterface;

interface AggregateRootInterface
{
    public function getId(): string;

    public function getVersion(): int;

    public function getUncommittedEvents(): array;

    public function markEventsAsCommitted(): void;

    public function applyEvent(DomainEventInterface $event): void;
}
