<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Domain\Port\AccountReadModelQuery;

class GetAccountBalanceHandler
{
    public function __construct(
        private AccountReadModelQuery $accountReadModel,
    ) {
    }

    public function handle(GetAccountBalanceQuery $query): ?AccountBalanceResponse
    {
        $data = $this->accountReadModel->getAccountBalance($query->getAccountId());

        if (null === $data) {
            return null;
        }

        return new AccountBalanceResponse(
            $data->accountId,
            $data->balance,
            $data->currency,
            $data->lastUpdated,
        );
    }
}
