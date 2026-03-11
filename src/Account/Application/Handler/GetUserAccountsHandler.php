<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetUserAccountsQuery;
use App\Account\Application\Query\Response\AccountSummary;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\Account\Domain\Port\AccountReadModelQuery;

class GetUserAccountsHandler
{
    public function __construct(
        private AccountReadModelQuery $accountReadModel,
    ) {
    }

    public function handle(GetUserAccountsQuery $query): UserAccountsResponse
    {
        $summaries = $this->accountReadModel->getUserAccountsSummary($query->getUserId());

        $accounts = array_map(
            fn ($data) => new AccountSummary(
                $data->accountId,
                $data->balance,
                $data->currency,
                $data->createdAt,
            ),
            $summaries
        );

        return new UserAccountsResponse(
            $query->getUserId(),
            $accounts
        );
    }
}
