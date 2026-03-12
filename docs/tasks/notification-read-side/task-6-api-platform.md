# Task 6 — API Platform StateProvider + DTO + ApiResource Operation

**Agent:** `symfony-ddd-developer`
**Parallel with:** none — depends on Task 4 (handler must exist)

## Instructions

Expose the notification history as a paginated GET endpoint via API Platform.

### 1. Create StateProvider: `src/Notification/Infrastructure/ApiPlatform/StateProvider/NotificationHistoryStateProvider.php`

Follow the pattern of `src/Account/Infrastructure/ApiPlatform/StateProvider/UserAccountsStateProvider.php`.

Namespace: `App\Notification\Infrastructure\ApiPlatform\StateProvider`

Implements: `ApiPlatform\State\ProviderInterface`

Constructor injects: `App\Notification\Application\Handler\GetNotificationHistoryHandler`

Method `provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null`:
1. Extract `$userId = $uriVariables['userId']` — throw `\InvalidArgumentException('User ID is required')` if missing
2. Extract pagination from `$context['filters']` if available: `page` (default 1), `perPage` (default 20)
3. Build `GetNotificationHistoryQuery($userId, $page, $perPage)`
4. Return `$this->handler->handle($query)`

### 2. Create API DTO: `src/Notification/Infrastructure/ApiPlatform/Dto/NotificationHistoryDto.php`

This is the API Platform resource class that defines the endpoint. Follow the pattern of how `src/Account/Domain/Entity/Account.php` declares its `#[ApiResource]` operations, but use a standalone DTO (notification history is read-only, no entity needed as resource).

Namespace: `App\Notification\Infrastructure\ApiPlatform\Dto`

```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

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
```

This is a minimal resource class. The actual response shape comes from `NotificationHistoryResponse` returned by the handler. API Platform will serialize whatever the provider returns.

### Verification

```bash
docker compose exec php vendor/bin/phpstan analyse src/Notification/Infrastructure/ApiPlatform/ --level=6
docker compose exec php bin/console debug:router | grep notification
```

Expected route: `GET /api/users/{userId}/notifications`
