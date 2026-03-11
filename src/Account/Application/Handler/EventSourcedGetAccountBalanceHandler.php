<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Query\GetAccountBalanceQuery;
use App\Account\Application\Query\Response\AccountBalanceResponse;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;

class EventSourcedGetAccountBalanceHandler
{
    public function __construct(
        private EventSourcedAccountRepositoryInterface $accountRepository,
    ) {
    }

    public function handle(GetAccountBalanceQuery $query): ?AccountBalanceResponse
    {
        $account = $this->accountRepository->findById($query->getAccountId());

        if (!$account) {
            return null;
        }

        return new AccountBalanceResponse(
            $account->getId(),
            $account->getBalance()->getAmount(),
            $account->getBalance()->getCurrency()->value,
            $account->getUpdatedAt()
        );
    }
}
