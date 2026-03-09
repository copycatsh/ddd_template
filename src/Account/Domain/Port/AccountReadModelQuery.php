<?php

namespace App\Account\Domain\Port;

interface AccountReadModelQuery
{
    public function getAccountBalance(string $accountId): ?AccountBalanceData;

    /**
     * @return AccountSummaryData[]
     */
    public function getUserAccountsSummary(string $userId): array;
}