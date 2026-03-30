<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Command\WithdrawMoneyCommand;
use App\Account\Application\Handler\WithdrawMoneyHandler;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Infrastructure\ApiPlatform\Dto\MoneyOperationDto;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;

class WithdrawMoneyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly WithdrawMoneyHandler $handler,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AccountResource
    {
        if (!$data instanceof MoneyOperationDto) {
            throw new \InvalidArgumentException('Expected MoneyOperationDto');
        }

        $accountId = $uriVariables['id'] ?? null;
        if (!$accountId) {
            throw new \InvalidArgumentException('Account ID is required');
        }

        $this->handler->handle(new WithdrawMoneyCommand($accountId, $data->getMoney()));

        $account = $this->accountRepository->findById($accountId);

        if (!$account) {
            throw new \RuntimeException(sprintf('Account %s not found after withdrawal', $accountId));
        }

        return AccountResource::fromAccount($account);
    }
}
