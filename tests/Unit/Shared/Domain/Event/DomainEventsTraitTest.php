<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEventInterface;
use App\Shared\Domain\Event\DomainEventsTrait;
use PHPUnit\Framework\TestCase;

class DomainEventsTraitTest extends TestCase
{
    public function testRecordEventAddsToCollection(): void
    {
        $entity = $this->createEntityWithTrait();
        $event = $this->createMock(DomainEventInterface::class);

        $entity->doRecordEvent($event);

        $this->assertCount(1, $entity->getUncommittedEvents());
        $this->assertSame($event, $entity->getUncommittedEvents()[0]);
    }

    public function testGetUncommittedEventsReturnsAll(): void
    {
        $entity = $this->createEntityWithTrait();
        $event1 = $this->createMock(DomainEventInterface::class);
        $event2 = $this->createMock(DomainEventInterface::class);

        $entity->doRecordEvent($event1);
        $entity->doRecordEvent($event2);

        $events = $entity->getUncommittedEvents();
        $this->assertCount(2, $events);
        $this->assertSame($event1, $events[0]);
        $this->assertSame($event2, $events[1]);
    }

    public function testMarkEventsAsCommittedClears(): void
    {
        $entity = $this->createEntityWithTrait();
        $entity->doRecordEvent($this->createMock(DomainEventInterface::class));
        $entity->doRecordEvent($this->createMock(DomainEventInterface::class));

        $this->assertCount(2, $entity->getUncommittedEvents());

        $entity->markEventsAsCommitted();

        $this->assertCount(0, $entity->getUncommittedEvents());
    }

    private function createEntityWithTrait(): object
    {
        return new class {
            use DomainEventsTrait;

            public function doRecordEvent(DomainEventInterface $event): void
            {
                $this->recordEvent($event);
            }
        };
    }
}
