# Task 4 — GetNotificationHistoryHandler Implementation

**Agent:** `symfony-ddd-developer`
**Parallel with:** none — depends on Task 1, Task 2, Task 3

## Instructions

Implement the query handler that makes the TDD test from Task 3 pass.

### File: `src/Notification/Application/Handler/GetNotificationHistoryHandler.php`

Follow the pattern of `src/Account/Application/Handler/GetUserAccountsHandler.php`.

Namespace: `App\Notification\Application\Handler`

### Constructor

Inject: `App\Notification\Domain\Port\NotificationHistoryQuery $notificationHistoryQuery`

### Method: `public function handle(GetNotificationHistoryQuery $query): NotificationHistoryResponse`

1. Call `$this->notificationHistoryQuery->getByUserId($query->getUserId(), $query->getPage(), $query->getPerPage())`
2. Call `$this->notificationHistoryQuery->countByUserId($query->getUserId())`
3. Map each `NotificationHistoryData` to `NotificationHistoryItem`:
   - All fields map directly except `sentAt` which must be formatted: `$data->sentAt->format('Y-m-d H:i:s')`
4. Return `new NotificationHistoryResponse($query->getUserId(), $items, $total, $query->getPage(), $query->getPerPage())`

### Verification

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/GetNotificationHistoryHandlerTest.php --testdox
```

All 3 tests must pass.
