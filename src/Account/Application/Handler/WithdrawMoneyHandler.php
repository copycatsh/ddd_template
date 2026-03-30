<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Domain\Exception\AccountNotFoundException;
use App\Account\Domain\Repository\AccountRepositoryInterface;

class WithdrawMoneyHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function handle(WithdrawMoneyCommand $command): void
    {
        $account = $this->accountRepository->findById($command->getAccountId());

        if (!$account) {
            throw AccountNotFoundException::withId($command->getAccountId());
        }

        $account->withdraw($command->getAmount());

        $this->accountRepository->save($account);
    }
}
