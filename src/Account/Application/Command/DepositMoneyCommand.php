<?php

namespace App\Account\Application\Command;

use App\Account\Domain\ValueObject\Money;

class DepositMoneyCommand
{
    public function __construct(
        private string $accountId,
        private Money $amount,
    ) {
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }
}
