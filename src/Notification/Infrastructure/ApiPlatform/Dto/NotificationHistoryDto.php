<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\ApiPlatform\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Notification\Infrastructure\ApiPlatform\StateProvider\NotificationHistoryStateProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/users/{userId}/notifications',
            provider: NotificationHistoryStateProvider::class,
            openapiContext: [
                'summary' => 'Get notification history for a user',
                'parameters' => [
                    ['name' => 'page', 'in' => 'query', 'type' => 'integer', 'required' => false],
                    ['name' => 'perPage', 'in' => 'query', 'type' => 'integer', 'required' => false],
                ],
            ],
        ),
    ],
)]
class NotificationHistoryDto
{
}
