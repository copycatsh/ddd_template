<?php

namespace App\Account\Domain\Port;

final class AccountSummaryData
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $balance,
        public readonly string $currency,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }
}
