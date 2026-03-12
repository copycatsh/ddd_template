<?php

namespace App\Notification\Application\Query\Response;

readonly class NotificationHistoryResponse
{
    public function __construct(
        public string $userId,
        /** @var NotificationHistoryItem[] */
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }
}
