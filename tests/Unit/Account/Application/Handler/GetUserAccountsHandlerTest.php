<?php

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Handler\GetUserAccountsHandler;
use App\Account\Application\Query\GetUserAccountsQuery;
use App\Account\Application\Query\Response\AccountSummary;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\Account\Domain\Port\AccountReadModelQuery;
use App\Account\Domain\Port\AccountSummaryData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetUserAccountsHandlerTest extends TestCase
{
    private AccountReadModelQuery&MockObject $readModel;
    private GetUserAccountsHandler $handler;

    protected function setUp(): void
    {
        $this->readModel = $this->createMock(AccountReadModelQuery::class);
        $this->handler = new GetUserAccountsHandler($this->readModel);
    }

    public function testHandleReturnsUserAccountsResponse(): void
    {
        $userId = 'user-456';
        $createdAt = new \DateTimeImmutable('2026-01-15');

        $summaries = [
            new AccountSummaryData('acc-1', '100.00', 'USD', $createdAt),
            new AccountSummaryData('acc-2', '200.50', 'EUR', $createdAt),
        ];

        $this->readModel
            ->expects($this->once())
            ->method('getUserAccountsSummary')
            ->with($userId)
            ->willReturn($summaries);

        $result = $this->handler->handle(new GetUserAccountsQuery($userId));

        $this->assertInstanceOf(UserAccountsResponse::class, $result);
        $this->assertSame($userId, $result->userId);
        $this->assertCount(2, $result->accounts);

        $this->assertInstanceOf(AccountSummary::class, $result->accounts[0]);
        $this->assertSame('acc-1', $result->accounts[0]->accountId);
        $this->assertSame('100.00', $result->accounts[0]->balance);
        $this->assertSame('USD', $result->accounts[0]->currency);

        $this->assertSame('acc-2', $result->accounts[1]->accountId);
        $this->assertSame('200.50', $result->accounts[1]->balance);
        $this->assertSame('EUR', $result->accounts[1]->currency);
    }

    public function testHandleReturnsEmptyResponseWhenNoAccounts(): void
    {
        $userId = 'user-no-accounts';

        $this->readModel
            ->expects($this->once())
            ->method('getUserAccountsSummary')
            ->with($userId)
            ->willReturn([]);

        $result = $this->handler->handle(new GetUserAccountsQuery($userId));

        $this->assertInstanceOf(UserAccountsResponse::class, $result);
        $this->assertSame($userId, $result->userId);
        $this->assertCount(0, $result->accounts);
    }
}
