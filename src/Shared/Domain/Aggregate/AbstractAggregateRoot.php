<?php

namespace App\Shared\Domain\Aggregate;

use App\Shared\Domain\Event\DomainEventInterface;
use App\Shared\Domain\Event\DomainEventsTrait;

abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    use DomainEventsTrait {
        DomainEventsTrait::recordEvent as private baseRecordEvent;
    }

    private string $id;
    private int $version = 0;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->baseRecordEvent($event);
        $this->applyEvent($event);
    }

    public function applyEvent(DomainEventInterface $event): void
    {
        ++$this->version;
        $this->when($event);
    }

    protected function when(DomainEventInterface $event): void
    {
        $method = $this->getEventHandlerMethod($event);

        if (method_exists($this, $method)) {
            $this->$method($event);
        }
    }

    private function getEventHandlerMethod(DomainEventInterface $event): string
    {
        $className = (new \ReflectionClass($event))->getShortName();

        return 'apply'.$className;
    }

    public static function reconstitute(string $id, array $events): static
    {
        $reflectionClass = new \ReflectionClass(static::class);
        $instance = $reflectionClass->newInstanceWithoutConstructor();
        $instance->id = $id;
        $instance->version = 0;
        $instance->markEventsAsCommitted();

        foreach ($events as $event) {
            $instance->applyEvent($event);
        }

        return $instance;
    }
}
