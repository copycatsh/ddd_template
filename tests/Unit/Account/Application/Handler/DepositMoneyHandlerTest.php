<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Application\Handler\DepositMoneyHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\AccountNotFoundException;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DepositMoneyHandlerTest extends TestCase
{
    private AccountRepositoryInterface&MockObject $accountRepository;
    private DepositMoneyHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->handler = new DepositMoneyHandler($this->accountRepository);
    }

    public function testHandleDepositsMoneyAndSaves(): void
    {
        $accountId = 'acc-123';
        $amount = new Money('100.00', Currency::UAH);
        $command = new DepositMoneyCommand($accountId, $amount);

        $account = new Account($accountId, 'user-123', Currency::UAH);

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

        $this->assertEquals('100.00', $account->getBalance()->getAmount());
    }

    public function testHandleThrowsWhenAccountNotFound(): void
    {
        $command = new DepositMoneyCommand('nonexistent', new Money('100.00', Currency::UAH));

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(AccountNotFoundException::class);
        $this->expectExceptionMessage('Account with ID nonexistent not found');

        $this->handler->handle($command);
    }

    public function testHandleLetsDomainExceptionBubbleUp(): void
    {
        $accountId = 'acc-123';
        $command = new DepositMoneyCommand($accountId, new Money('100.00', Currency::USD));

        $account = new Account($accountId, 'user-123', Currency::UAH);

        $this->accountRepository
            ->expects($this->once())
            ->method('findById')
            ->with($accountId)
            ->willReturn($account);

        $this->expectException(\App\Account\Domain\Exception\CurrencyMismatchException::class);

        $this->handler->handle($command);
    }
}
