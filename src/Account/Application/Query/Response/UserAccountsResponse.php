<?php

namespace App\Account\Application\Query\Response;

class UserAccountsResponse
{
    public function __construct(
        public readonly string $userId,
        /** @var AccountSummary[] */
        public readonly array $accounts,
    ) {
    }
}
