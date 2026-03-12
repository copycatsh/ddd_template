# Task 3 — Unit Test for GetNotificationHistoryHandler (TDD)

**Agent:** `php-test-writer`
**Parallel with:** none — depends on Task 1 + Task 2 (needs port interface and query/response classes)

## Instructions

Write a unit test for `GetNotificationHistoryHandler` BEFORE the handler is implemented (TDD).

### File: `tests/Unit/Notification/Application/Handler/GetNotificationHistoryHandlerTest.php`

Follow the exact pattern of `tests/Unit/Notification/Application/Handler/TransactionCreatedNotificationHandlerTest.php`:
- PHPUnit 11+ attributes: `#[CoversClass(...)]`, `#[Test]`
- Intersection types for mocks: `NotificationHistoryQuery&MockObject`
- `setUp()` creates mocks and instantiates handler

### Namespace: `App\Tests\Unit\Notification\Application\Handler`

### Handler under test (not yet created, but you know its shape)

Class: `App\Notification\Application\Handler\GetNotificationHistoryHandler`
- Constructor: injects `NotificationHistoryQuery` (from `App\Notification\Domain\Port`)
- Method: `public function handle(GetNotificationHistoryQuery $query): NotificationHistoryResponse`

### Test cases

1. **testReturnsNotificationHistoryForUser** — Mock `getByUserId` returning 2 `NotificationHistoryData` items + `countByUserId` returning 2. Assert the response contains 2 `NotificationHistoryItem` objects with correct field mapping (especially `sentAt` formatted as `'Y-m-d H:i:s'` string). Assert `$response->total === 2`, `$response->userId`, `$response->page`, `$response->perPage`.

2. **testReturnsEmptyResponseWhenNoNotifications** — Mock `getByUserId` returning `[]`, `countByUserId` returning `0`. Assert response has empty `items` array and `total === 0`.

3. **testPassesPaginationParametersToPort** — Create query with `page=3, perPage=10`. Assert mock expects `getByUserId($userId, 3, 10)` with exact arguments.

### Mock data helper

Use these values for `NotificationHistoryData`:
- `id: 1`, `transactionId: 'txn-001'`, `accountId: 'acc-001'`, `recipientEmail: 'user@example.com'`, `notificationType: 'transaction_created'`, `sentAt: new \DateTimeImmutable('2026-03-10 14:30:00')`

### Verification

```bash
docker compose exec php vendor/bin/phpunit tests/Unit/Notification/Application/Handler/GetNotificationHistoryHandlerTest.php --testdox
```

This test WILL FAIL (class not found) — that is expected for TDD. The test must be syntactically correct and ready to pass once Task 4 creates the handler.
