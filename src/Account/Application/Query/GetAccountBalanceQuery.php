<?php

namespace App\Account\Application\Query;

class GetAccountBalanceQuery
{
    public function __construct(
        private string $accountId,
    ) {
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }
}
