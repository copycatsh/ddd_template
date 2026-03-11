<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\WithdrawMoneyCommand;
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
            throw new \InvalidArgumentException('Account not found');
        }

        // Validate currency match
        if (!$command->getAmount()->getCurrency()->equals($account->getCurrency())) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        // Validate amount is positive
        if (bccomp($command->getAmount()->getAmount(), '0', 2) <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        // Withdraw money using domain logic (includes insufficient funds check)
        $account->withdraw($command->getAmount());

        // Save changes
        $this->accountRepository->save($account);
    }
}
