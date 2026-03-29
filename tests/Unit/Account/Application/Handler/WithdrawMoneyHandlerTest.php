<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Application\Handler\WithdrawMoneyHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\AccountNotFoundException;
use App\Account\Domain\Exception\CurrencyMismatchException;
use App\Account\Domain\Exception\InsufficientFundsException;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WithdrawMoneyHandlerTest extends TestCase
{
    private AccountRepositoryInterface&MockObject $accountRepository;
    private WithdrawMoneyHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->handler = new WithdrawMoneyHandler($this->accountRepository);
    }

    public function testHandleWithdrawsMoneyAndSaves(): void
    {
        $accountId = 'acc-123';
        $command = new WithdrawMoneyCommand($accountId, new Money('30.00', Currency::UAH));

        $account = new Account($accountId, 'user-123', Currency::UAH);
        $account->deposit(new Money('100.00', Currency::UAH));

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $this->accountRepository
            ->expects($this->once())
            ->method('save')
            ->with($account);

        $this->handler->handle($command);

        $this->assertEquals('70.00', $account->getBalance()->getAmount());
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
        $this->expectExceptionMessage('Account with ID nonexistent not found');

        $this->handler->handle($command);
    }

    public function testHandleLetsCurrencyMismatchBubbleUp(): void
    {
        $accountId = 'acc-123';
        $command = new WithdrawMoneyCommand($accountId, new Money('50.00', Currency::USD));

        $account = new Account($accountId, 'user-123', Currency::UAH);
        $account->deposit(new Money('100.00', Currency::UAH));

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $this->expectException(CurrencyMismatchException::class);

        $this->handler->handle($command);
    }

    public function testHandleLetsInsufficientFundsBubbleUp(): void
    {
        $accountId = 'acc-123';
        $command = new WithdrawMoneyCommand($accountId, new Money('200.00', Currency::UAH));

        $account = new Account($accountId, 'user-123', Currency::UAH);
        $account->deposit(new Money('100.00', Currency::UAH));

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $this->expectException(InsufficientFundsException::class);

        $this->handler->handle($command);
    }
}
