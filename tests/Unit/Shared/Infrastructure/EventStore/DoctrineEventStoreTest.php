<?php

namespace App\Tests\Unit\Shared\Infrastructure\EventStore;

use App\Account\Domain\Event\AccountCreatedEvent;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Infrastructure\EventStore\DoctrineEventStore;
use App\Transaction\Domain\Event\TransactionCompletedEvent;
use App\Transaction\Domain\Event\TransactionCreatedEvent;
use App\Transaction\Domain\Event\TransactionFailedEvent;
use App\Transaction\Domain\ValueObject\TransactionType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\TestCase;

class DoctrineEventStoreTest extends TestCase
{
    private function makeStore(array $rows): DoctrineEventStore
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($rows);

        $stmt = $this->createMock(Statement::class);
        $stmt->method('executeQuery')->willReturn($result);

        $connection = $this->createMock(Connection::class);
        $connection->method('prepare')->willReturn($stmt);

        return new DoctrineEventStore($connection);
    }

    private function makeRow(string $eventClass, array $data): array
    {
        return [
            'event_type' => $eventClass,
            'event_data' => json_encode($data),
            'version' => 1,
            'occurred_at' => '2026-01-01 00:00:00',
        ];
    }

    public function testDeserializesTransactionCreatedEvent(): void
    {
        $transactionId = 'txn-123';
        $accountId = 'acc-456';

        $row = $this->makeRow(TransactionCreatedEvent::class, [
            'transactionId' => $transactionId,
            'accountId' => $accountId,
            'type' => TransactionType::TRANSFER->value,
            'amount' => '50.00',
            'currency' => 'UAH',
        ]);

        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate($transactionId);

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(TransactionCreatedEvent::class, $event);
        $this->assertSame($transactionId, $event->getTransactionId());
        $this->assertSame($accountId, $event->getAccountId());
        $this->assertSame(TransactionType::TRANSFER, $event->getType());
        $this->assertSame('50.00', $event->getAmount());
        $this->assertSame('UAH', $event->getCurrency());
    }

    public function testDeserializesTransactionCompletedEvent(): void
    {
        $transactionId = 'txn-789';
        $accountId = 'acc-101';

        $row = $this->makeRow(TransactionCompletedEvent::class, [
            'transactionId' => $transactionId,
            'accountId' => $accountId,
        ]);

        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate($transactionId);

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(TransactionCompletedEvent::class, $event);
        $this->assertSame($transactionId, $event->getTransactionId());
        $this->assertSame($accountId, $event->getAccountId());
    }

    public function testDeserializesTransactionFailedEvent(): void
    {
        $transactionId = 'txn-fail';
        $accountId = 'acc-202';

        $row = $this->makeRow(TransactionFailedEvent::class, [
            'transactionId' => $transactionId,
            'accountId' => $accountId,
            'reason' => 'Insufficient funds',
        ]);

        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate($transactionId);

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(TransactionFailedEvent::class, $event);
        $this->assertSame($transactionId, $event->getTransactionId());
        $this->assertSame($accountId, $event->getAccountId());
        $this->assertSame('Insufficient funds', $event->getReason());
    }

    public function testDeserializesTransactionFailedEventWithNullReason(): void
    {
        $transactionId = 'txn-fail-null';
        $accountId = 'acc-303';

        $row = $this->makeRow(TransactionFailedEvent::class, [
            'transactionId' => $transactionId,
            'accountId' => $accountId,
            'reason' => null,
        ]);

        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate($transactionId);

        $event = $events[0];
        $this->assertInstanceOf(TransactionFailedEvent::class, $event);
        $this->assertNull($event->getReason());
    }

    public function testDeserializesAccountCreatedEvent(): void
    {
        $accountId = 'acc-created';

        $row = $this->makeRow(AccountCreatedEvent::class, [
            'accountId' => $accountId,
            'userId' => 'user-111',
            'currency' => 'UAH',
        ]);

        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate($accountId);

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(AccountCreatedEvent::class, $event);
        $this->assertSame($accountId, $event->getAccountId());
        $this->assertSame(Currency::UAH, $event->getCurrency());
    }

    public function testRoundTripTransactionCreatedEvent(): void
    {
        $original = new TransactionCreatedEvent(
            'txn-rt',
            'acc-rt',
            TransactionType::DEPOSIT,
            '100.00',
            'USD',
        );

        $row = $this->makeRow($original->getEventType(), $original->getEventData());

        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate('txn-rt');

        $restored = $events[0];
        $this->assertInstanceOf(TransactionCreatedEvent::class, $restored);
        $this->assertSame($original->getEventData(), $restored->getEventData());
    }

    public function testRoundTripTransactionCompletedEvent(): void
    {
        $original = new TransactionCompletedEvent('txn-c', 'acc-c');

        $row = $this->makeRow($original->getEventType(), $original->getEventData());
        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate('txn-c');

        $restored = $events[0];
        $this->assertInstanceOf(TransactionCompletedEvent::class, $restored);
        $this->assertSame($original->getEventData(), $restored->getEventData());
    }

    public function testRoundTripTransactionFailedEvent(): void
    {
        $original = new TransactionFailedEvent('txn-f', 'acc-f', 'timeout');

        $row = $this->makeRow($original->getEventType(), $original->getEventData());
        $store = $this->makeStore([$row]);
        $events = $store->getEventsForAggregate('txn-f');

        $restored = $events[0];
        $this->assertInstanceOf(TransactionFailedEvent::class, $restored);
        $this->assertSame($original->getEventData(), $restored->getEventData());
    }
}
