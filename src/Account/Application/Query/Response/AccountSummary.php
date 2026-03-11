<?php

namespace App\Account\Application\Query\Response;

class AccountSummary
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $balance,
        public readonly string $currency,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
