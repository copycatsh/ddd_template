<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

trait DomainEventsTrait
{
    /** @var list<DomainEventInterface> */
    private array $uncommittedEvents = [];

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->uncommittedEvents[] = $event;
    }

    /** @return DomainEventInterface[] */
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }
}
