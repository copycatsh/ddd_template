<?php

namespace App\Account\Domain\Port;

readonly class TransferActivityData
{
    public function __construct(
        public string $dailyTotal,
        public int $dailyCount,
        public \DateTimeImmutable $date,
    ) {
    }
}
