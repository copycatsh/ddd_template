<?php

namespace App\Account\Domain\Port;

interface TransferActivityQuery
{
    public function getDailyActivity(string $accountId, \DateTimeImmutable $date): TransferActivityData;
}
