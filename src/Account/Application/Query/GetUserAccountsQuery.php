<?php

namespace App\Account\Application\Query;

class GetUserAccountsQuery
{
    public function __construct(
        private string $userId,
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
