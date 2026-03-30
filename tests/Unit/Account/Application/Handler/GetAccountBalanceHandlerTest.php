<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Handler\GetAccountBalanceHandler;
use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetAccountBalanceHandlerTest extends TestCase
{
    private EventSourcedAccountRepositoryInterface&MockObject $accountRepository;
    private GetAccountBalanceHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(EventSourcedAccountRepositoryInterface::class);
        $this->handler = new GetAccountBalanceHandler($this->accountRepository);
    }

    public function testHandleReturnsBalanceResponse(): void
    {
        $account = EventSourcedAccount::create('acc-1', 'user-1', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('250.00', Currency::UAH));

        $query = new GetAccountBalanceQuery('acc-1');

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('acc-1')
            ->willReturn($account);

        $response = $this->handler->handle($query);

        $this->assertInstanceOf(AccountBalanceResponse::class, $response);
        $this->assertEquals('acc-1', $response->accountId);
        $this->assertEquals('250.00', $response->balance);
        $this->assertEquals('UAH', $response->currency);
    }

    public function testHandleReturnsNullWhenAccountNotFound(): void
    {
        $query = new GetAccountBalanceQuery('nonexistent');

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $response = $this->handler->handle($query);

        $this->assertNull($response);
    }
}
