<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Account\Application\Handler\GetAccountTransactionsHandler;
use App\Account\Application\Query\GetAccountTransactionsQuery;

class AccountTransactionsStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly GetAccountTransactionsHandler $handler,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $accountId = $uriVariables['id'] ?? null;

        if (!$accountId) {
            throw new \InvalidArgumentException('Account ID is required');
        }

        $query = new GetAccountTransactionsQuery($accountId);

        return $this->handler->handle($query);
    }
}
