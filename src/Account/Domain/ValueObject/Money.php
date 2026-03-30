<?php

namespace App\Account\Domain\ValueObject;

use App\Account\Domain\Exception\CurrencyMismatchException;
use App\Shared\Domain\Exception\InvalidAmountException;
use App\Shared\Domain\Exception\NegativeBalanceException;

class Money
{
    private string $amount;
    private Currency $currency;

    // Zero is a valid amount (e.g., new account balance).
    // Deposit/withdraw reject zero separately as a meaningless operation.
    public function __construct(string $amount, Currency $currency)
    {
        if (bccomp($amount, '0', 2) < 0) {
            throw InvalidAmountException::negativeAmount();
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function equals(Money $other): bool
    {
        return 0 === bccomp($this->amount, $other->amount, 2)
            && $this->currency->equals($other->currency);
    }

    public function add(Money $other): Money
    {
        if (!$this->currency->equals($other->currency)) {
            throw CurrencyMismatchException::forOperation($this->currency, $other->currency);
        }

        return new Money(bcadd($this->amount, $other->amount, 2), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if (!$this->currency->equals($other->currency)) {
            throw CurrencyMismatchException::forOperation($this->currency, $other->currency);
        }

        $result = bcsub($this->amount, $other->amount, 2);

        if (bccomp($result, '0', 2) < 0) {
            throw NegativeBalanceException::fromSubtraction($result);
        }

        return new Money($result, $this->currency);
    }
}
