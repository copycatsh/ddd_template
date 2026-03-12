# Task 5 — Doctrine DBAL Read Adapter for NotificationHistoryQuery

**Agent:** `symfony-ddd-developer`
**Parallel with:** Task 3, Task 4 (no file dependency — only needs port from Task 1)

## Instructions

Implement the infrastructure adapter that reads notification history via raw DBAL.

### File: `src/Notification/Infrastructure/Query/DoctrineNotificationHistoryQuery.php`

Follow the pattern of `src/Account/Infrastructure/Repository/DoctrineAccountReadModelQuery.php` and `src/Notification/Infrastructure/Query/DoctrineNotificationAccountQuery.php`.

Namespace: `App\Notification\Infrastructure\Query`

### Constructor

Inject: `Doctrine\DBAL\Connection $connection`

### Implements: `App\Notification\Domain\Port\NotificationHistoryQuery`

### Method: `getByUserId(string $userId, int $page = 1, int $perPage = 20): array`

Raw DBAL query against `notification_log` table:
```sql
SELECT id, transaction_id, account_id, recipient_email, notification_type, sent_at
FROM notification_log
WHERE user_id = :userId
ORDER BY sent_at DESC
LIMIT :limit OFFSET :offset
```

Calculate offset: `($page - 1) * $perPage`

Map each row to `NotificationHistoryData`:
- `(int) $row['id']`
- `$row['transaction_id']`
- `$row['account_id']`
- `$row['recipient_email']`
- `$row['notification_type']`
- `new \DateTimeImmutable($row['sent_at'])`

Use `$this->connection->fetchAllAssociative()` with parameter types for LIMIT/OFFSET:
```php
$this->connection->fetchAllAssociative($sql, [
    'userId' => $userId,
    'limit' => $perPage,
    'offset' => ($page - 1) * $perPage,
], [
    'userId' => \Doctrine\DBAL\ParameterType::STRING,
    'limit' => \Doctrine\DBAL\ParameterType::INTEGER,
    'offset' => \Doctrine\DBAL\ParameterType::INTEGER,
]);
```

### Method: `countByUserId(string $userId): int`

```sql
SELECT COUNT(*) FROM notification_log WHERE user_id = :userId
```

Use `$this->connection->fetchOne()` and cast to `(int)`.

### Wire in services.yaml

Add to `config/services.yaml` after the existing Account port alias:

```yaml
App\Notification\Domain\Port\NotificationHistoryQuery:
    alias: App\Notification\Infrastructure\Query\DoctrineNotificationHistoryQuery
```

### Verification

```bash
docker compose exec php vendor/bin/phpstan analyse src/Notification/Infrastructure/Query/DoctrineNotificationHistoryQuery.php --level=6
```
