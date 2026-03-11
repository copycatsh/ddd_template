<?php

namespace App\Notification\Domain\Port;

readonly class NotificationUserData
{
    public function __construct(
        public string $userId,
        public string $email,
    ) {
    }
}
