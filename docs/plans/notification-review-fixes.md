# Notification Bounded Context — Review Fixes

Date: 2026-03-08
Branch: `feature/notification-bounded-context`

## Tasks

### Task 1 — Add `NotificationType` backed enum

Create `src/Notification/Domain/ValueObject/NotificationType.php`:

```php
enum NotificationType: string
{
    case TransactionCreated   = 'transaction_created';
    case TransactionCompleted = 'transaction_completed';
    case TransactionFailed    = 'transaction_failed';
}
```

Update `NotificationLog`:
- Change constructor parameter `string $notificationType` → `NotificationType $notificationType`
- Store `$notificationType->value` in the column (or use Doctrine `enumType`)
- Update `getNotificationType(): NotificationType`

---

### Task 2 — Add domain exceptions for null port returns

Create:
- `src/Notification/Domain/Exception/NotificationUserNotFoundException.php`
- `src/Notification/Domain/Exception/NotificationAccountNotFoundException.php`

Both extend `\DomainException`. These must exist before handlers (Tasks 8–10) are written so that a null return from `NotificationUserQuery` or `NotificationAccountQuery` is never silently discarded.

---

### Task 3 — Fix `bigint` → `integer` column type in `NotificationLog`

In `src/Notification/Domain/Entity/NotificationLog.php`:

```php
// Before
#[ORM\Column(type: 'bigint')]
private ?int $id = null;

// After
#[ORM\Column(type: 'integer')]
private ?int $id = null;
```

Doctrine `bigint` maps to `string` in PHP, not `int`. `integer` matches the `?int` property type.

---

### Task 4 — Add construction-time validation to `NotificationLog`

In the `NotificationLog` constructor, add guards before assignments:

```php
if (trim($transactionId) === '' || trim($accountId) === '' || trim($userId) === '') {
    throw new \InvalidArgumentException('IDs must be non-empty.');
}
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    throw new \InvalidArgumentException("Invalid recipient email: $recipientEmail");
}
```

---

### Task 5 — Place saga dispatch outside the DB transaction boundary (Task 11 constraint)

When `MessageBusInterface` is added to `TransferMoneySaga` (Task 11), the `dispatch()` calls must be placed **after** `$this->entityManager->commit()`, not inside the try block that wraps the financial transaction.

Rationale: the broad `catch (\Exception $e)` in the saga re-wraps all exceptions as `RuntimeException`, which bypasses `DomainExceptionSubscriber`. A notification failure must not roll back a completed financial transaction. Notification dispatch is a side effect, not part of the atomic unit.

Sketch:

```php
$this->entityManager->commit();

// Outside the financial transaction boundary:
$this->messageBus->dispatch(new TransactionCreatedEvent(...));
```

Handle `Messenger` dispatch exceptions independently (log and continue, or let them surface as 500s without affecting the transfer result).