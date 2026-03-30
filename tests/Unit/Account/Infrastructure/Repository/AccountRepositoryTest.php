<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use App\Account\Infrastructure\Repository\AccountRepository;
use App\Shared\Infrastructure\EventStore\EventStoreInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(AccountRepository::class)]
final class AccountRepositoryTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private MessageBusInterface&MockObject $messageBus;
    private Connection&MockObject $connection;
    private AccountRepository $repository;

    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->repository = new AccountRepository(
            $this->eventStore,
            $this->messageBus,
            $this->connection,
        );
    }

    #[Test]
    public function testSaveDispatchesEventsViaMessageBus(): void
    {
        $accountId = 'acc-123';
        $userId = 'user-456';
        $currency = Currency::UAH;

        $account = Account::create($accountId, $userId, $currency);
        $account->deposit(new Money('100.00', $currency));

        $events = $account->getUncommittedEvents();
        self::assertCount(2, $events);

        $this->eventStore
            ->expects(self::once())
            ->method('saveEvents')
            ->with($accountId, $events, 0);

        $this->messageBus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->connection
            ->expects(self::once())
            ->method('beginTransaction');

        $this->connection
            ->expects(self::once())
            ->method('commit');

        $this->connection
            ->expects(self::never())
            ->method('rollBack');

        $this->repository->save($account);

        self::assertEmpty($account->getUncommittedEvents());
    }

    #[Test]
    public function testSaveRollsBackOnDispatchFailure(): void
    {
        $accountId = 'acc-123';
        $userId = 'user-456';
        $currency = Currency::UAH;

        $account = Account::create($accountId, $userId, $currency);
        $events = $account->getUncommittedEvents();

        $this->eventStore
            ->expects(self::once())
            ->method('saveEvents')
            ->with($accountId, $events, 0);

        $this->messageBus
            ->expects(self::once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('Dispatch failed'));

        $this->connection
            ->expects(self::once())
            ->method('beginTransaction');

        $this->connection
            ->expects(self::once())
            ->method('rollBack');

        $this->connection
            ->expects(self::never())
            ->method('commit');

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Dispatch failed');

        $this->repository->save($account);

        self::assertNotEmpty($account->getUncommittedEvents());
    }

    #[Test]
    public function testSaveShortCircuitsOnEmptyEvents(): void
    {
        $accountId = 'acc-123';
        $userId = 'user-456';
        $currency = Currency::UAH;

        $account = Account::create($accountId, $userId, $currency);
        $account->markEventsAsCommitted();

        self::assertEmpty($account->getUncommittedEvents());

        $this->connection
            ->expects(self::never())
            ->method('beginTransaction');

        $this->eventStore
            ->expects(self::never())
            ->method('saveEvents');

        $this->messageBus
            ->expects(self::never())
            ->method('dispatch');

        $this->connection
            ->expects(self::never())
            ->method('commit');

        $this->connection
            ->expects(self::never())
            ->method('rollBack');

        $this->repository->save($account);
    }

    #[Test]
    public function testSaveRollsBackOnEventStoreFailure(): void
    {
        $accountId = 'acc-123';
        $userId = 'user-456';
        $currency = Currency::UAH;

        $account = Account::create($accountId, $userId, $currency);
        $events = $account->getUncommittedEvents();

        $this->eventStore
            ->expects(self::once())
            ->method('saveEvents')
            ->with($accountId, $events, 0)
            ->willThrowException(new \RuntimeException('Event store failed'));

        $this->messageBus
            ->expects(self::never())
            ->method('dispatch');

        $this->connection
            ->expects(self::once())
            ->method('beginTransaction');

        $this->connection
            ->expects(self::once())
            ->method('rollBack');

        $this->connection
            ->expects(self::never())
            ->method('commit');

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Event store failed');

        $this->repository->save($account);

        self::assertNotEmpty($account->getUncommittedEvents());
    }
}
