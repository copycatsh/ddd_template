<?php

namespace App\Account\Domain\Port;

readonly class AccountProjectionData
{
    public function __construct(
        public string $accountId,
        public string $userId,
        public string $currency,
        public string $balance,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
