<?php

namespace App\Notification\Application\Query\Response;

readonly class NotificationHistoryItem
{
    public function __construct(
        public int $id,
        public string $transactionId,
        public string $accountId,
        public string $userId,
        public string $recipientEmail,
        public string $notificationType,
        public string $sentAt,
    ) {
    }
}
