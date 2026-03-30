<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Handler\GetUserAccountsHandler;
use App\Account\Application\Query\GetUserAccountsQuery;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\Account\Domain\Port\AccountProjectionData;
use App\Account\Domain\Port\AccountProjectionQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetUserAccountsHandlerTest extends TestCase
{
    private AccountProjectionQuery&MockObject $projectionQuery;
    private GetUserAccountsHandler $handler;

    protected function setUp(): void
    {
        $this->projectionQuery = $this->createMock(AccountProjectionQuery::class);
        $this->handler = new GetUserAccountsHandler($this->projectionQuery);
    }

    public function testHandleReturnsUserAccounts(): void
    {
        $query = new GetUserAccountsQuery('user-1');

        $this->projectionQuery
            ->expects($this->once())
            ->method('findByUserId')
            ->with('user-1')
            ->willReturn([
                new AccountProjectionData(
                    'acc-1', 'user-1', 'UAH', '100.00',
                    new \DateTimeImmutable('2026-01-01'),
                    new \DateTimeImmutable('2026-01-01'),
                ),
                new AccountProjectionData(
                    'acc-2', 'user-1', 'USD', '50.00',
                    new \DateTimeImmutable('2026-01-02'),
                    new \DateTimeImmutable('2026-01-02'),
                ),
            ]);

        $response = $this->handler->handle($query);

        $this->assertInstanceOf(UserAccountsResponse::class, $response);
        $this->assertEquals('user-1', $response->userId);
        $this->assertCount(2, $response->accounts);
        $this->assertEquals('acc-1', $response->accounts[0]->accountId);
        $this->assertEquals('100.00', $response->accounts[0]->balance);
        $this->assertEquals('acc-2', $response->accounts[1]->accountId);
        $this->assertEquals('50.00', $response->accounts[1]->balance);
    }

    public function testHandleReturnsEmptyListWhenNoAccounts(): void
    {
        $query = new GetUserAccountsQuery('user-no-accounts');

        $this->projectionQuery
            ->expects($this->once())
            ->method('findByUserId')
            ->with('user-no-accounts')
            ->willReturn([]);

        $response = $this->handler->handle($query);

        $this->assertInstanceOf(UserAccountsResponse::class, $response);
        $this->assertEquals('user-no-accounts', $response->userId);
        $this->assertCount(0, $response->accounts);
    }
}
