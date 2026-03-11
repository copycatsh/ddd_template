<?php

namespace App\Account\Domain\ValueObject;

class Money
{
    private string $amount;
    private Currency $currency;

    public function __construct(string $amount, Currency $currency)
    {
        if (bccomp($amount, '0', 2) < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
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
            throw new \InvalidArgumentException('Currency mismatch');
        }

        return new Money(bcadd($this->amount, $other->amount, 2), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if (!$this->currency->equals($other->currency)) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        $result = bcsub($this->amount, $other->amount, 2);

        if (bccomp($result, '0', 2) < 0) {
            throw new \InvalidArgumentException('Result cannot be negative');
        }

        return new Money($result, $this->currency);
    }
}
