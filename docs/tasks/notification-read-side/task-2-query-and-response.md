# Task 2 — Application Query + Response DTOs

**Agent:** `symfony-ddd-developer`
**Parallel with:** Task 1 (no file dependency, only namespace reference)

## Instructions

Create the query object and response DTO in the Application layer.

### 1. Create Query: `src/Notification/Application/Query/GetNotificationHistoryQuery.php`

Follow the pattern of `src/Account/Application/Query/GetUserAccountsQuery.php`.

Namespace: `App\Notification\Application\Query`

Properties (private readonly, with getters):
- `string $userId`
- `int $page` (default `1`)
- `int $perPage` (default `20`)

### 2. Create Response DTO: `src/Notification/Application/Query/Response/NotificationHistoryItem.php`

Follow the pattern of `src/Account/Application/Query/Response/AccountSummary.php`.

Namespace: `App\Notification\Application\Query\Response`

Properties (public readonly):
- `int $id`
- `string $transactionId`
- `string $accountId`
- `string $recipientEmail`
- `string $notificationType`
- `string $sentAt` (formatted as `'Y-m-d H:i:s'` string, not DateTimeImmutable — this is an API-facing DTO)

### 3. Create Response DTO: `src/Notification/Application/Query/Response/NotificationHistoryResponse.php`

Follow the pattern of `src/Account/Application/Query/Response/UserAccountsResponse.php`.

Namespace: `App\Notification\Application\Query\Response`

Properties (public readonly):
- `string $userId`
- `array $items` (with `@var NotificationHistoryItem[]` docblock)
- `int $total`
- `int $page`
- `int $perPage`

### Verification

```bash
docker compose exec php vendor/bin/phpstan analyse src/Notification/Application/Query/ --level=6
```
