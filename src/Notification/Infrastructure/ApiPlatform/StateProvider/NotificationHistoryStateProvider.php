<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\ApiPlatform\StateProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Notification\Application\Handler\GetNotificationHistoryHandler;
use App\Notification\Application\Query\GetNotificationHistoryQuery;

/** @implements ProviderInterface<object> */
class NotificationHistoryStateProvider implements ProviderInterface
{
    public function __construct(
        private GetNotificationHistoryHandler $handler,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $userId = $uriVariables['userId'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $filters = $context['filters'] ?? [];
        $page = isset($filters['page']) ? (int) $filters['page'] : 1;
        $perPage = isset($filters['perPage']) ? (int) $filters['perPage'] : 20;

        $query = new GetNotificationHistoryQuery($userId, $page, $perPage);

        return ($this->handler)($query);
    }
}
