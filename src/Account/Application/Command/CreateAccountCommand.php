<?php

namespace App\Account\Application\Command;

use App\Account\Domain\ValueObject\Currency;

class CreateAccountCommand
{
    public function __construct(
        private string $userId,
        private Currency $currency,
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }
}
