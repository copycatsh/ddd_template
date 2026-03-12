# Task 1 — Domain Port: NotificationHistoryQuery interface + NotificationHistoryData DTO

**Agent:** `symfony-ddd-developer`
**Parallel with:** none (foundational — all other tasks depend on this)

## Instructions

Create the read port for notification history in the Notification bounded context.

### 1. Create DTO: `src/Notification/Domain/Port/NotificationHistoryData.php`

Follow the pattern of `src/Account/Domain/Port/AccountSummaryData.php` — a `readonly class` .

Namespace: `App\Notification\Domain\Port`

Properties (all from `notification_log` table):
- `int $id`
- `string $transactionId`
- `string $accountId`
- `string $userId`
- `string $recipientEmail`
- `string $notificationType` (string, not the enum — this is a read DTO)
- `\DateTimeImmutable $sentAt`

### 2. Create Port Interface: `src/Notification/Domain/Port/NotificationHistoryQuery.php`

Follow the pattern of `src/Account/Domain/Port/AccountReadModelQuery.php`.

Namespace: `App\Notification\Domain\Port`

```
interface NotificationHistoryQuery
{
    /**
     * @return NotificationHistoryData[]
     */
    public function getByUserId(string $userId, int $page = 1, int $perPage = 20): array;

    public function countByUserId(string $userId): int;
}
```

Two methods: paginated fetch and total count (needed for paginated API responses).

### Verification

```bash
docker compose exec php vendor/bin/phpstan analyse src/Notification/Domain/Port/NotificationHistoryData.php src/Notification/Domain/Port/NotificationHistoryQuery.php --level=6
```
