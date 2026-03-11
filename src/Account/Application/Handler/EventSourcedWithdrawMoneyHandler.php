<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;

class EventSourcedWithdrawMoneyHandler
{
    public function __construct(
        private EventSourcedAccountRepositoryInterface $accountRepository,
    ) {
    }

    public function handle(WithdrawMoneyCommand $command): void
    {
        $account = $this->accountRepository->findById($command->getAccountId());

        if (!$account) {
            throw new \DomainException('Account not found');
        }

        $account->withdraw($command->getAmount());

        $this->accountRepository->save($account);
    }
}
