<?php

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Handler\GetAccountBalanceHandler;
use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Domain\Port\AccountBalanceData;
use App\Account\Domain\Port\AccountReadModelQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetAccountBalanceHandlerTest extends TestCase
{
    private AccountReadModelQuery&MockObject $readModel;
    private GetAccountBalanceHandler $handler;

    protected function setUp(): void
    {
        $this->readModel = $this->createMock(AccountReadModelQuery::class);
        $this->handler = new GetAccountBalanceHandler($this->readModel);
    }

    public function testHandleReturnsBalanceResponseWhenAccountExists(): void
    {
        $accountId = 'acc-123';
        $lastUpdated = new \DateTimeImmutable('2026-03-09 10:00:00');

        $data = new AccountBalanceData($accountId, '150.00', 'USD', $lastUpdated);

        $this->readModel
            ->expects($this->once())
            ->method('getAccountBalance')
            ->with($accountId)
            ->willReturn($data);

        $result = $this->handler->handle(new GetAccountBalanceQuery($accountId));

        $this->assertInstanceOf(AccountBalanceResponse::class, $result);
        $this->assertSame($accountId, $result->accountId);
        $this->assertSame('150.00', $result->balance);
        $this->assertSame('USD', $result->currency);
        $this->assertSame($lastUpdated, $result->lastUpdated);
    }

    public function testHandleReturnsNullWhenAccountNotFound(): void
    {
        $accountId = 'nonexistent';

        $this->readModel
            ->expects($this->once())
            ->method('getAccountBalance')
            ->with($accountId)
            ->willReturn(null);

        $result = $this->handler->handle(new GetAccountBalanceQuery($accountId));

        $this->assertNull($result);
    }
}
