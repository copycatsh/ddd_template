<?php

namespace App\Account\Application\Command;

use App\Account\Domain\ValueObject\Money;

class TransferMoneyCommand
{
    public function __construct(
        private string $fromAccountId,
        private string $toAccountId,
        private Money $amount,
    ) {
    }

    public function getFromAccountId(): string
    {
        return $this->fromAccountId;
    }

    public function getToAccountId(): string
    {
        return $this->toAccountId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }
}
