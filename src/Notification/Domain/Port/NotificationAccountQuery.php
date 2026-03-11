<?php

namespace App\Notification\Domain\Port;

interface NotificationAccountQuery
{
    public function findByAccountId(string $accountId): ?NotificationAccountData;
}
