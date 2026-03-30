<?php

namespace App\Account\Infrastructure\ApiPlatform\StateProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Account\Application\Handler\GetUserAccountsHandler;
use App\Account\Application\Query\GetUserAccountsQuery;

class UserAccountsStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly GetUserAccountsHandler $handler,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $userId = $uriVariables['userId'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $query = new GetUserAccountsQuery($userId);

        return $this->handler->handle($query);
    }
}
