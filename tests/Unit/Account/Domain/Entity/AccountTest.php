<?php

namespace App\Tests\Unit\Account\Domain\Entity;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\Event\AccountCreatedEvent;
use App\Account\Domain\Event\MoneyDepositedEvent;
use App\Account\Domain\Event\MoneyWithdrawnEvent;
use App\Account\Domain\Exception\InsufficientFundsException;
use App\Shared\Domain\Exception\CurrencyMismatchException;
use App\Shared\Domain\Exception\InvalidAmountException;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    public function testAccountCreation()
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $this->assertEquals(0.00, $account->getBalance()->getAmount());
        $this->assertEquals(Currency::UAH, $account->getBalance()->getCurrency());
        $this->assertEquals('user-id', $account->getUserId());
    }

    public function testDepositRecordsEvent()
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();

        $money = new Money('100.50', Currency::UAH);
        $account->deposit($money);

        $this->assertEquals('100.50', $account->getBalance()->getAmount());

        $events = $account->getUncommittedEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(MoneyDepositedEvent::class, $events[0]);

        $this->assertEquals(2, $account->getVersion());
    }

    public function testWithdrawRecordsEvent()
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();

        $money1 = new Money('100.50', Currency::UAH);
        $money2 = new Money('30', Currency::UAH);
        $account->deposit($money1);

        $this->assertEquals('100.50', $account->getBalance()->getAmount());

        $account->withdraw($money2);
        $this->assertEquals('70.50', $account->getBalance()->getAmount());

        $events = $account->getUncommittedEvents();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(MoneyDepositedEvent::class, $events[0]);
        $this->assertInstanceOf(MoneyWithdrawnEvent::class, $events[1]);

        $this->assertEquals('30', $events[1]->getAmount()->getAmount());
        $this->assertEquals('70.50', $events[1]->getNewBalance());

        $this->assertEquals(3, $account->getVersion());
    }

    public function testAccountCreationRecordsEvent(): void
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $events = $account->getUncommittedEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(AccountCreatedEvent::class, $events[0]);
        $this->assertEquals(1, $account->getVersion());
    }

    public function testReconstitute(): void
    {
        $events = [
            new AccountCreatedEvent('test-id', 'user-id', Currency::UAH),
            new MoneyDepositedEvent('test-id', new Money('100.00', Currency::UAH), '100.00'),
        ];

        $account = Account::reconstitute('test-id', $events);

        $this->assertEquals('100.00', $account->getBalance()->getAmount());
        $this->assertEquals(2, $account->getVersion());
        $this->assertCount(0, $account->getUncommittedEvents()); // reconstitute не створює uncommitted events
    }

    public function testDepositZeroAmountThrowsException(): void
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();

        $this->expectException(InvalidAmountException::class);

        $account->deposit(new Money('0.00', Currency::UAH));
    }

    public function testWithdrawZeroAmountThrowsException(): void
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('100.00', Currency::UAH));
        $account->markEventsAsCommitted();

        $this->expectException(InvalidAmountException::class);

        $account->withdraw(new Money('0.00', Currency::UAH));
    }

    public function testDepositWithDifferentCurrencyThrowsException(): void
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();

        $this->expectException(CurrencyMismatchException::class);

        $account->deposit(new Money('100.00', Currency::USD));
    }

    public function testWithdrawWithDifferentCurrencyThrowsException(): void
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('100.00', Currency::UAH));
        $account->markEventsAsCommitted();

        $this->expectException(CurrencyMismatchException::class);

        $account->withdraw(new Money('100.00', Currency::USD));
    }

    public function testWithdrawWithInsufficientFundsThrowsException(): void
    {
        $account = Account::create('test-id', 'user-id', Currency::UAH);
        $account->markEventsAsCommitted();
        $account->deposit(new Money('50.00', Currency::UAH));
        $account->markEventsAsCommitted();

        $this->expectException(InsufficientFundsException::class);

        $account->withdraw(new Money('100.00', Currency::UAH));
    }

    public function testCreateWithEmptyUserIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot be empty');

        Account::create('test-id', '', Currency::UAH);
    }

    public function testCreateWithWhitespaceUserIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot be empty');

        Account::create('test-id', '   ', Currency::UAH);
    }
}
