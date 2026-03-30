<?php

namespace App\Account\Domain\Port;

interface AccountProjectionQuery
{
    public function findByAccountId(string $accountId): ?AccountProjectionData;

    public function findByUserIdAndCurrency(string $userId, string $currency): ?AccountProjectionData;

    /** @return AccountProjectionData[] */
    public function findByUserId(string $userId): array;
}
