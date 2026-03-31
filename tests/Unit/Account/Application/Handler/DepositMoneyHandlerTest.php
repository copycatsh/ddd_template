<?php

declare(strict_types=1);

namespace App\Tests\Unit\Account\Application\Handler;

use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Application\Handler\DepositMoneyHandler;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\AccountNotFoundException;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
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

    public function testHandleDepositsMoneySuccessfully(): void
    {
        $account = Account::create('acc-1', 'user-1', Currency::UAH);
        $account->markEventsAsCommitted();

        $command = new DepositMoneyCommand('acc-1', new Money('100.00', Currency::UAH));

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
}
