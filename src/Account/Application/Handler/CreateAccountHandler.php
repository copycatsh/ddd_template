<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Domain\Entity\Account;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use Symfony\Component\Uid\Uuid;

class CreateAccountHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function handle(CreateAccountCommand $command): string
    {
        $accountId = Uuid::v4()->toRfc4122();

        $account = new Account(
            $accountId,
            $command->getUserId(),
            $command->getCurrency()
        );

        $this->accountRepository->save($account);

        return $accountId;
    }
}
