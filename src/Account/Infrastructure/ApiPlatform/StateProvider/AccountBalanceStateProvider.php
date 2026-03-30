<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Account\Application\Handler\GetAccountBalanceHandler;
use App\Account\Application\Query\GetAccountBalanceQuery;

class AccountBalanceStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly GetAccountBalanceHandler $handler,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $accountId = $uriVariables['id'] ?? null;
        if (!$accountId) {
            throw new \InvalidArgumentException('Account ID is required');
        }

        $query = new GetAccountBalanceQuery($accountId);

        return $this->handler->handle($query);
    }
}
