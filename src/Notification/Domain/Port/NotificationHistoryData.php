<?php

namespace App\Notification\Domain\Port;

readonly class NotificationHistoryData
{
    public function __construct(
        public int $id,
        public string $transactionId,
        public string $accountId,
        public string $userId,
        public string $recipientEmail,
        public string $notificationType,
        public \DateTimeImmutable $sentAt,
    ) {
    }
}
