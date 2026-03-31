<?php

namespace App\Account\Domain\Specification\Transfer;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;

final readonly class TransferRequest
{
    public function __construct(
        public string $fromAccountId,
        public string $toAccountId,
        public Currency $fromCurrency,
        public Currency $toCurrency,
        public Money $amount,
    ) {
    }
}
