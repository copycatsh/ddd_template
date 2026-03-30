<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetUserAccountsQuery;
use App\Account\Application\Query\Response\AccountSummary;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\Account\Domain\Port\AccountProjectionQuery;

class GetUserAccountsHandler
{
    public function __construct(
        private AccountProjectionQuery $projectionQuery,
    ) {
    }

    public function handle(GetUserAccountsQuery $query): UserAccountsResponse
    {
        $accounts = $this->projectionQuery->findByUserId($query->getUserId());

        $summaries = array_map(function ($data) {
            return new AccountSummary(
                $data->accountId,
                $data->balance,
                $data->currency,
                $data->createdAt,
            );
        }, $accounts);

        return new UserAccountsResponse($query->getUserId(), $summaries);
    }
}
