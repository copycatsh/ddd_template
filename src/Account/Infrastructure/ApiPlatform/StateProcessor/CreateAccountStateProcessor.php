<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Command\CreateAccountCommand;
use App\Account\Application\Handler\CreateAccountHandler;
use App\Account\Domain\Repository\AccountRepositoryInterface;
use App\Account\Domain\ValueObject\Currency;
use App\Account\Infrastructure\ApiPlatform\Dto\CreateAccountDto;
use App\Account\Infrastructure\ApiPlatform\Resource\AccountResource;

class CreateAccountStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateAccountHandler $handler,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AccountResource
    {
        if (!$data instanceof CreateAccountDto) {
            throw new \InvalidArgumentException(sprintf('Expected CreateAccountDto, got %s', get_debug_type($data)));
        }

        $command = new CreateAccountCommand($data->userId, Currency::from($data->currency));

        $accountId = $this->handler->handle($command);

        $account = $this->accountRepository->findById($accountId);

        if (!$account) {
            throw new \RuntimeException(sprintf('Account %s not found after creation', $accountId));
        }

        return AccountResource::fromAccount($account);
    }
}
