<?php

namespace App\Notification\Domain\Port;

interface NotificationHistoryQuery
{
    /**
     * @return NotificationHistoryData[]
     */
    public function getByUserId(string $userId, int $page = 1, int $perPage = 20): array;

    public function countByUserId(string $userId): int;
}
