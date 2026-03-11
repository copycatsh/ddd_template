<?php

namespace App\Account\Application\Query\Response;

class AccountBalanceResponse
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $balance,
        public readonly string $currency,
        public readonly \DateTimeImmutable $lastUpdated,
    ) {
    }
}
