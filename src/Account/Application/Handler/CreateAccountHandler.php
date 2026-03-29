<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Domain\Exception\AccountAlreadyExistsException;
use App\Account\Domain\Factory\AccountFactory;
use App\Account\Domain\Repository\AccountRepositoryInterface;

class CreateAccountHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactory $accountFactory,
    ) {
    }

    public function handle(CreateAccountCommand $command): string
    {
        $existingAccount = $this->accountRepository->findByUserIdAndCurrency(
            $command->getUserId(),
            $command->getCurrency(),
        );

        if ($existingAccount) {
            throw AccountAlreadyExistsException::forUserAndCurrency($command->getUserId(), $command->getCurrency());
        }

        $account = $this->accountFactory->create(
            $command->getUserId(),
            $command->getCurrency(),
        );

        $this->accountRepository->save($account);

        return $account->getId();
    }
}
