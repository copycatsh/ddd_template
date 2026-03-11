<?php

namespace App\Notification\Domain\Port;

interface NotificationUserQuery
{
    public function findByUserId(string $userId): ?NotificationUserData;
}
