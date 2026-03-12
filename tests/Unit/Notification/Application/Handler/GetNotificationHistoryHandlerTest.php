<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Handler;

use App\Notification\Application\Handler\GetNotificationHistoryHandler;
use App\Notification\Application\Query\GetNotificationHistoryQuery;
use App\Notification\Application\Query\Response\NotificationHistoryResponse;
use App\Notification\Domain\Port\NotificationHistoryData;
use App\Notification\Domain\Port\NotificationHistoryQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GetNotificationHistoryHandler::class)]
final class GetNotificationHistoryHandlerTest extends TestCase
{
    private NotificationHistoryQuery&MockObject $historyQuery;
    private GetNotificationHistoryHandler $handler;

    protected function setUp(): void
    {
        $this->historyQuery = $this->createMock(NotificationHistoryQuery::class);

        $this->handler = new GetNotificationHistoryHandler(
            $this->historyQuery,
        );
    }

    #[Test]
    public function testReturnsNotificationHistoryForUser(): void
    {
        $userId = 'user-123';
        $query = new GetNotificationHistoryQuery($userId);

        $historyData1 = new NotificationHistoryData(
            id: 1,
            transactionId: 'txn-001',
            accountId: 'acc-001',
            userId: $userId,
            recipientEmail: 'user@example.com',
            notificationType: 'transaction_created',
            sentAt: new \DateTimeImmutable('2026-03-10 14:30:00'),
        );

        $historyData2 = new NotificationHistoryData(
            id: 2,
            transactionId: 'txn-002',
            accountId: 'acc-001',
            userId: $userId,
            recipientEmail: 'user@example.com',
            notificationType: 'transaction_completed',
            sentAt: new \DateTimeImmutable('2026-03-11 09:15:00'),
        );

        $this->historyQuery
            ->expects(self::once())
            ->method('getByUserId')
            ->with($userId, 1, 20)
            ->willReturn([$historyData1, $historyData2]);

        $this->historyQuery
            ->expects(self::once())
            ->method('countByUserId')
            ->with($userId)
            ->willReturn(2);

        $response = ($this->handler)($query);

        self::assertInstanceOf(NotificationHistoryResponse::class, $response);
        self::assertSame(2, count($response->items));
        self::assertSame(2, $response->total);
        self::assertSame($userId, $response->userId);
        self::assertSame(1, $response->page);
        self::assertSame(20, $response->perPage);

        self::assertSame(1, $response->items[0]->id);
        self::assertSame('txn-001', $response->items[0]->transactionId);
        self::assertSame('acc-001', $response->items[0]->accountId);
        self::assertSame('user@example.com', $response->items[0]->recipientEmail);
        self::assertSame('transaction_created', $response->items[0]->notificationType);
        self::assertSame('2026-03-10 14:30:00', $response->items[0]->sentAt);

        self::assertSame(2, $response->items[1]->id);
        self::assertSame('txn-002', $response->items[1]->transactionId);
        self::assertSame('2026-03-11 09:15:00', $response->items[1]->sentAt);
    }

    #[Test]
    public function testReturnsEmptyResponseWhenNoNotifications(): void
    {
        $userId = 'user-456';
        $query = new GetNotificationHistoryQuery($userId);

        $this->historyQuery
            ->expects(self::once())
            ->method('getByUserId')
            ->with($userId, 1, 20)
            ->willReturn([]);

        $this->historyQuery
            ->expects(self::once())
            ->method('countByUserId')
            ->with($userId)
            ->willReturn(0);

        $response = ($this->handler)($query);

        self::assertInstanceOf(NotificationHistoryResponse::class, $response);
        self::assertSame(0, count($response->items));
        self::assertSame(0, $response->total);
        self::assertSame($userId, $response->userId);
    }

    #[Test]
    public function testPassesPaginationParametersToPort(): void
    {
        $userId = 'user-789';
        $page = 3;
        $perPage = 10;
        $query = new GetNotificationHistoryQuery($userId, $page, $perPage);

        $this->historyQuery
            ->expects(self::once())
            ->method('getByUserId')
            ->with($userId, 3, 10)
            ->willReturn([]);

        $this->historyQuery
            ->expects(self::once())
            ->method('countByUserId')
            ->with($userId)
            ->willReturn(0);

        $response = ($this->handler)($query);

        self::assertSame(3, $response->page);
        self::assertSame(10, $response->perPage);
    }
}
