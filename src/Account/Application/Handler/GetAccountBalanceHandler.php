<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Domain\Port\AccountProjectionQuery;

class GetAccountBalanceHandler
{
    public function __construct(
        private AccountProjectionQuery $projectionQuery,
    ) {
    }

    public function handle(GetAccountBalanceQuery $query): ?AccountBalanceResponse
    {
        $data = $this->projectionQuery->findByAccountId($query->getAccountId());

        if (!$data) {
            return null;
        }

        return new AccountBalanceResponse(
            $data->accountId,
            $data->balance,
            $data->currency,
            $data->updatedAt,
        );
    }
}
