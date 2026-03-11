<?php

namespace App\Account\Application\Query;

class GetAccountTransactionsQuery
{
    public function __construct(
        private readonly string $accountId,
    ) {
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }
}
