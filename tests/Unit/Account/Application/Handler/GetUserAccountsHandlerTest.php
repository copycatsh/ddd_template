<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Handler\GetUserAccountsHandler;
use App\Account\Application\Query\GetUserAccountsQuery;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetUserAccountsHandlerTest extends TestCase
{
    private EventSourcedAccountRepositoryInterface&MockObject $accountRepository;
    private GetUserAccountsHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(EventSourcedAccountRepositoryInterface::class);
        $this->handler = new GetUserAccountsHandler($this->accountRepository);
    }

    public function testHandleReturnsUserAccounts(): void
    {
        $account1 = EventSourcedAccount::create('acc-1', 'user-1', Currency::UAH);
        $account1->markEventsAsCommitted();
        $account1->deposit(new Money('100.00', Currency::UAH));

        $account2 = EventSourcedAccount::create('acc-2', 'user-1', Currency::USD);
        $account2->markEventsAsCommitted();
        $account2->deposit(new Money('50.00', Currency::USD));

        $query = new GetUserAccountsQuery('user-1');

        $this->accountRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with('user-1')
            ->willReturn([$account1, $account2]);

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

        $this->accountRepository
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
