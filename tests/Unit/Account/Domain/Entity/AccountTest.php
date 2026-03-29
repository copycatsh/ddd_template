<?php

namespace App\Tests\Unit\Account\Domain\Entity;

use App\Account\Domain\Entity\Account;
use App\Account\Domain\Exception\CurrencyMismatchException;
use App\Account\Domain\Exception\InsufficientFundsException;
use App\Account\Domain\Exception\InvalidAmountException;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    private Account $account;

    protected function setUp(): void
    {
        $this->account = new Account(
            id: 'test-account-id',
            userId: 'test-user-id',
            currency: Currency::UAH
        );
    }

    public function testAccountCreation(): void
    {
        $this->assertEquals('test-account-id', $this->account->getId());
        $this->assertEquals('test-user-id', $this->account->getUserId());
        $this->assertEquals(Currency::UAH, $this->account->getCurrency());
        $this->assertEquals('0.00', $this->account->getBalance()->getAmount());
    }

    public function testAccountHasCreationTimestamp(): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->account->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->account->getUpdatedAt());
    }

    public function testDeposit(): void
    {
        $money = new Money('100.50', Currency::UAH);
        $this->account->deposit($money);

        $this->assertEquals('100.50', $this->account->getBalance()->getAmount());
    }

    public function testMultipleDeposits(): void
    {
        $this->account->deposit(new Money('100.00', Currency::UAH));
        $this->account->deposit(new Money('50.25', Currency::UAH));
        $this->account->deposit(new Money('25.75', Currency::UAH));

        $this->assertEquals('176.00', $this->account->getBalance()->getAmount());
    }

    public function testWithdraw(): void
    {
        $this->account->deposit(new Money('100.00', Currency::UAH));
        $this->account->withdraw(new Money('30.25', Currency::UAH));

        $this->assertEquals('69.75', $this->account->getBalance()->getAmount());
    }

    public function testDepositUpdatesTimestamp(): void
    {
        $initialTimestamp = $this->account->getUpdatedAt();

        sleep(1); // Wait 1 second to ensure timestamp changes

        $this->account->deposit(new Money('100.00', Currency::UAH));

        $this->assertGreaterThan(
            $initialTimestamp->getTimestamp(),
            $this->account->getUpdatedAt()->getTimestamp()
        );
    }

    public function testWithdrawUpdatesTimestamp(): void
    {
        $this->account->deposit(new Money('100.00', Currency::UAH));
        $initialTimestamp = $this->account->getUpdatedAt();

        sleep(1); // Wait 1 second to ensure timestamp changes

        $this->account->withdraw(new Money('50.00', Currency::UAH));

        $this->assertGreaterThan(
            $initialTimestamp->getTimestamp(),
            $this->account->getUpdatedAt()->getTimestamp()
        );
    }

    public function testDepositWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        $this->expectExceptionMessage('Currency mismatch. Account currency: UAH, Operation currency: USD');

        $this->account->deposit(new Money('100.00', Currency::USD));
    }

    public function testWithdrawWithDifferentCurrencyThrowsException(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        $this->expectExceptionMessage('Currency mismatch. Account currency: UAH, Operation currency: USD');

        $this->account->deposit(new Money('100.00', Currency::UAH));
        $this->account->withdraw(new Money('50.00', Currency::USD));
    }

    public function testWithdrawWithInsufficientFundsThrowsException(): void
    {
        $this->expectException(InsufficientFundsException::class);
        $this->expectExceptionMessage('Insufficient funds. Available: 0.00, Requested: 100.00');

        $this->account->withdraw(new Money('100.00', Currency::UAH));
    }

    public function testWithdrawExactBalance(): void
    {
        $this->account->deposit(new Money('100.00', Currency::UAH));
        $this->account->withdraw(new Money('100.00', Currency::UAH));

        $this->assertEquals('0.00', $this->account->getBalance()->getAmount());
    }

    public function testComplexScenario(): void
    {
        // Initial deposit
        $this->account->deposit(new Money('1000.00', Currency::UAH));
        $this->assertEquals('1000.00', $this->account->getBalance()->getAmount());

        // Withdraw some money
        $this->account->withdraw(new Money('350.50', Currency::UAH));
        $this->assertEquals('649.50', $this->account->getBalance()->getAmount());

        // Add more money
        $this->account->deposit(new Money('100.75', Currency::UAH));
        $this->assertEquals('750.25', $this->account->getBalance()->getAmount());

        // Final withdrawal
        $this->account->withdraw(new Money('250.25', Currency::UAH));
        $this->assertEquals('500.00', $this->account->getBalance()->getAmount());
    }

    public function testDepositZeroAmountThrowsException(): void
    {
        $this->expectException(InvalidAmountException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->account->deposit(new Money('0.00', Currency::UAH));
    }

    public function testWithdrawZeroAmountThrowsException(): void
    {
        $this->account->deposit(new Money('100.00', Currency::UAH));

        $this->expectException(InvalidAmountException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->account->withdraw(new Money('0.00', Currency::UAH));
    }

    public function testNegativeAmountRejectedByMoneyConstructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative');

        new Money('-10.00', Currency::UAH);
    }

    public function testNegativeAmountCannotReachDeposit(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $negativeMoney = new Money('-50.00', Currency::UAH);
        $this->account->deposit($negativeMoney);
    }

    public function testNegativeAmountCannotReachWithdraw(): void
    {
        $this->account->deposit(new Money('100.00', Currency::UAH));

        $this->expectException(\InvalidArgumentException::class);

        $negativeMoney = new Money('-50.00', Currency::UAH);
        $this->account->withdraw($negativeMoney);
    }

    public function testBalanceReturnsMoneyValueObject(): void
    {
        $this->account->deposit(new Money('100.00', Currency::UAH));

        $balance = $this->account->getBalance();

        $this->assertInstanceOf(Money::class, $balance);
        $this->assertEquals('100.00', $balance->getAmount());
        $this->assertEquals(Currency::UAH, $balance->getCurrency());
    }
}
