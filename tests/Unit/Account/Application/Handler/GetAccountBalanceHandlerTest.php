<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Handler\GetAccountBalanceHandler;
use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Domain\Port\AccountProjectionData;
use App\Account\Domain\Port\AccountProjectionQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetAccountBalanceHandlerTest extends TestCase
{
    private AccountProjectionQuery&MockObject $projectionQuery;
    private GetAccountBalanceHandler $handler;

    protected function setUp(): void
    {
        $this->projectionQuery = $this->createMock(AccountProjectionQuery::class);
        $this->handler = new GetAccountBalanceHandler($this->projectionQuery);
    }

    public function testHandleReturnsBalanceResponse(): void
    {
        $query = new GetAccountBalanceQuery('acc-1');

        $this->projectionQuery
            ->expects($this->once())
            ->method('findByAccountId')
            ->with('acc-1')
            ->willReturn(new AccountProjectionData(
                'acc-1', 'user-1', 'UAH', '250.00',
                new \DateTimeImmutable('2026-01-01'),
                new \DateTimeImmutable('2026-01-02'),
            ));

        $response = $this->handler->handle($query);

        $this->assertInstanceOf(AccountBalanceResponse::class, $response);
        $this->assertEquals('acc-1', $response->accountId);
        $this->assertEquals('250.00', $response->balance);
        $this->assertEquals('UAH', $response->currency);
    }

    public function testHandleReturnsNullWhenAccountNotFound(): void
    {
        $query = new GetAccountBalanceQuery('nonexistent');

        $this->projectionQuery
            ->expects($this->once())
            ->method('findByAccountId')
            ->with('nonexistent')
            ->willReturn(null);

        $this->assertNull($this->handler->handle($query));
    }
}
