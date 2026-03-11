<?php

namespace App\Notification\Domain\Port;

readonly class NotificationAccountData
{
    public function __construct(
        public string $accountId,
        public string $userId,
        public string $currency,
    ) {
    }
}
