<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\DepositMoneyCommand;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;

class EventSourcedDepositMoneyHandler
{
    public function __construct(
        private EventSourcedAccountRepositoryInterface $accountRepository,
    ) {
    }

    public function handle(DepositMoneyCommand $command): void
    {
        $account = $this->accountRepository->findById($command->getAccountId());

        if (!$account) {
            throw new \DomainException('Account not found');
        }

        $account->deposit($command->getAmount());

        $this->accountRepository->save($account);
    }
}
