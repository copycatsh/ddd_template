<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Integration;

use App\Shared\Integration\Event\TransactionCompletedIntegrationEvent;
use App\Shared\Integration\Event\TransactionCreatedIntegrationEvent;
use App\Shared\Integration\Event\TransactionFailedIntegrationEvent;
use App\Shared\Integration\IntegrationEventMapper;
use App\Transaction\Domain\Event\TransactionCompletedEvent;
use App\Transaction\Domain\Event\TransactionCreatedEvent;
use App\Transaction\Domain\Event\TransactionFailedEvent;
use App\Transaction\Domain\ValueObject\TransactionType;
use PHPUnit\Framework\TestCase;

class IntegrationEventMapperTest extends TestCase
{
    private IntegrationEventMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new IntegrationEventMapper();
    }

    public function testMapsTransactionCreatedEvent(): void
    {
        $domainEvent = new TransactionCreatedEvent('txn-1', 'acc-1', TransactionType::TRANSFER, '500.00', 'UAH');

        $result = $this->mapper->map($domainEvent);

        $this->assertInstanceOf(TransactionCreatedIntegrationEvent::class, $result);
        $this->assertSame('txn-1', $result->transactionId);
        $this->assertSame('acc-1', $result->accountId);
        $this->assertSame('500.00', $result->amount);
        $this->assertSame('UAH', $result->currency);
    }

    public function testMapsTransactionCompletedEvent(): void
    {
        $domainEvent = new TransactionCompletedEvent('txn-2', 'acc-2');

        $result = $this->mapper->map($domainEvent);

        $this->assertInstanceOf(TransactionCompletedIntegrationEvent::class, $result);
        $this->assertSame('txn-2', $result->transactionId);
        $this->assertSame('acc-2', $result->accountId);
    }

    public function testMapsTransactionFailedEvent(): void
    {
        $domainEvent = new TransactionFailedEvent('txn-3', 'acc-3', 'Insufficient funds');

        $result = $this->mapper->map($domainEvent);

        $this->assertInstanceOf(TransactionFailedIntegrationEvent::class, $result);
        $this->assertSame('txn-3', $result->transactionId);
        $this->assertSame('acc-3', $result->accountId);
        $this->assertSame('Insufficient funds', $result->reason);
    }

    public function testMapsTransactionFailedEventWithNullReason(): void
    {
        $domainEvent = new TransactionFailedEvent('txn-4', 'acc-4');

        $result = $this->mapper->map($domainEvent);

        $this->assertInstanceOf(TransactionFailedIntegrationEvent::class, $result);
        $this->assertNull($result->reason);
    }

    public function testThrowsForUnsupportedEvent(): void
    {
        $unsupported = new class extends \App\Shared\Domain\Event\AbstractDomainEvent {
            public function __construct()
            {
                parent::__construct();
            }

            public function getAggregateId(): string
            {
                return 'x';
            }

            public function getEventData(): array
            {
                return [];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported domain event');

        $this->mapper->map($unsupported);
    }
}
