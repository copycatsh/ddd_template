<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Saga\TransferMoneySaga;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Infrastructure\ApiPlatform\Dto\TransferMoneyDto;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;

class TransferMoneyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TransferMoneySaga $saga,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AccountResource
    {
        if (!$data instanceof TransferMoneyDto) {
            throw new \InvalidArgumentException('Expected TransferMoneyDto');
        }

        $fromAccountId = $uriVariables['id'] ?? null;
        if (!$fromAccountId) {
            throw new \InvalidArgumentException('Account ID is required');
        }

        $this->saga->execute(
            $fromAccountId,
            $data->toAccountId,
            $data->getMoney()
        );

        $account = $this->accountRepository->findById($fromAccountId);

        if (!$account) {
            throw new \RuntimeException(sprintf('Account %s not found after transfer', $fromAccountId));
        }

        return AccountResource::fromAccount($account);
    }
}
