<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Infrastructure\Projection;

use App\Account\Domain\Port\AccountProjectionData;
use App\Account\Infrastructure\Projection\DoctrineAccountProjectionQuery;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineAccountProjectionQueryTest extends TestCase
{
    private Connection&MockObject $connection;
    private DoctrineAccountProjectionQuery $query;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->query = new DoctrineAccountProjectionQuery($this->connection);
    }

    public function testFindByAccountIdReturnsDataWhenFound(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 'acc-1',
                'user_id' => 'user-1',
                'currency' => 'UAH',
                'balance' => '100.00',
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-02 00:00:00',
            ]);

        $result = $this->query->findByAccountId('acc-1');

        $this->assertInstanceOf(AccountProjectionData::class, $result);
        $this->assertEquals('acc-1', $result->accountId);
        $this->assertEquals('user-1', $result->userId);
        $this->assertEquals('UAH', $result->currency);
        $this->assertEquals('100.00', $result->balance);
    }

    public function testFindByAccountIdReturnsNullWhenNotFound(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(false);

        $this->assertNull($this->query->findByAccountId('nonexistent'));
    }

    public function testFindByUserIdAndCurrencyReturnsDataWhenFound(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => 'acc-1',
                'user_id' => 'user-1',
                'currency' => 'UAH',
                'balance' => '50.00',
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-01 00:00:00',
            ]);

        $result = $this->query->findByUserIdAndCurrency('user-1', 'UAH');

        $this->assertInstanceOf(AccountProjectionData::class, $result);
        $this->assertEquals('acc-1', $result->accountId);
    }

    public function testFindByUserIdAndCurrencyReturnsNullWhenNotFound(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(false);

        $this->assertNull($this->query->findByUserIdAndCurrency('user-1', 'UAH'));
    }

    public function testFindByUserIdReturnsMultipleAccounts(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                [
                    'id' => 'acc-1', 'user_id' => 'user-1', 'currency' => 'UAH',
                    'balance' => '100.00', 'created_at' => '2026-01-01 00:00:00',
                    'updated_at' => '2026-01-01 00:00:00',
                ],
                [
                    'id' => 'acc-2', 'user_id' => 'user-1', 'currency' => 'USD',
                    'balance' => '50.00', 'created_at' => '2026-01-02 00:00:00',
                    'updated_at' => '2026-01-02 00:00:00',
                ],
            ]);

        $results = $this->query->findByUserId('user-1');

        $this->assertCount(2, $results);
        $this->assertEquals('acc-1', $results[0]->accountId);
        $this->assertEquals('acc-2', $results[1]->accountId);
    }

    public function testFindByUserIdReturnsEmptyArrayWhenNoAccounts(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([]);

        $this->assertSame([], $this->query->findByUserId('user-no-accounts'));
    }
}
