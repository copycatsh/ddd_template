<?php

namespace App\Account\Application\Handler;

use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Domain\Entity\EventSourcedAccount;
use App\Account\Domain\Exception\AccountAlreadyExistsException;
use App\Account\Domain\Port\AccountProjectionQuery;
use App\Account\Domain\Repository\EventSourcedAccountRepositoryInterface;
use Symfony\Component\Uid\Uuid;

class CreateAccountHandler
{
    public function __construct(
        private EventSourcedAccountRepositoryInterface $accountRepository,
        private AccountProjectionQuery $projectionQuery,
    ) {
    }

    public function handle(CreateAccountCommand $command): string
    {
        $existingAccount = $this->projectionQuery->findByUserIdAndCurrency(
            $command->getUserId(),
            $command->getCurrency()->value
        );

        if ($existingAccount) {
            throw AccountAlreadyExistsException::forUserAndCurrency($command->getUserId(), $command->getCurrency());
        }

        $accountId = Uuid::v4()->toRfc4122();

        $account = EventSourcedAccount::create(
            $accountId,
            $command->getUserId(),
            $command->getCurrency()
        );

        $this->accountRepository->save($account);

        return $accountId;
    }
}
