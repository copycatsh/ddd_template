<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Domain\Exception\AccountNotFoundException;
use App\Account\Domain\Repository\AccountRepositoryInterface;

class DepositMoneyHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function handle(DepositMoneyCommand $command): void
    {
        $account = $this->accountRepository->findById($command->getAccountId());

        if (!$account) {
            throw AccountNotFoundException::withId($command->getAccountId());
        }

        $account->deposit($command->getAmount());

        $this->accountRepository->save($account);
    }
}
