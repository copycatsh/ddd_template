<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetUserAccountsQuery;
use App\Account\Application\Query\Response\AccountSummary;
use App\Account\Application\Query\Response\UserAccountsResponse;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;

class EventSourcedGetUserAccountsHandler
{
    public function __construct(
        private EventSourcedAccountRepositoryInterface $accountRepository,
    ) {
    }

    public function handle(GetUserAccountsQuery $query): UserAccountsResponse
    {
        $accounts = $this->accountRepository->findByUserId($query->getUserId());

        $summaries = array_map(function ($account) {
            return new AccountSummary(
                $account->getId(),
                $account->getBalance()->getAmount(),
                $account->getBalance()->getCurrency()->value,
                $account->getCreatedAt()
            );
        }, $accounts);

        return new UserAccountsResponse($query->getUserId(), $summaries);
    }
}
