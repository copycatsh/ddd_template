<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Application\Handler\WithdrawMoneyHandler;
use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Exception\AccountNotFoundException;
use App\Account\Domain\Exception\InsufficientFundsException;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WithdrawMoneyHandlerTest extends TestCase
{
    private EventSourcedAccountRepositoryInterface&MockObject $accountRepository;
    private WithdrawMoneyHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(EventSourcedAccountRepositoryInterface::class);
        $this->handler = new WithdrawMoneyHandler($this->accountRepository);
    }

    public function testHandleWithdrawsMoneySuccessfully(): void
    {
        $account = EventSourcedAccount::create('acc-1', 'user-1', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('200.00', Currency::UAH));
        $account->markEventsAsCommitted();

        $command = new WithdrawMoneyCommand('acc-1', new Money('50.00', Currency::UAH));

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('acc-1')
            ->willReturn($account);

        $this->accountRepository
            ->expects($this->once())
            ->method('save')
            ->with($account);

        $this->handler->handle($command);

        $this->assertEquals('150.00', $account->getBalance()->getAmount());
    }

    public function testHandleThrowsWhenAccountNotFound(): void
    {
        $command = new WithdrawMoneyCommand('nonexistent', new Money('50.00', Currency::UAH));

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(AccountNotFoundException::class);

        $this->handler->handle($command);
    }

    public function testHandleThrowsWhenInsufficientFunds(): void
    {
        $account = EventSourcedAccount::create('acc-1', 'user-1', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('50.00', Currency::UAH));
        $account->markEventsAsCommitted();

        $command = new WithdrawMoneyCommand('acc-1', new Money('100.00', Currency::UAH));

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('acc-1')
            ->willReturn($account);

        $this->expectException(InsufficientFundsException::class);

        $this->handler->handle($command);
    }
}
