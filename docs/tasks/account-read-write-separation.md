# DDD Cleanup Tasks

Technical debt identified from the DDD structure analysis. Tasks are independent and can be worked in any order.

---

## Task 1: Remove legacy `DomainEvent` interface

### Description

`src/Shared/Domain/Event/DomainEvent.php` is a legacy interface predating `DomainEventInterface`. It defines the same four methods but omits `getVersion(): int`. `AbstractDomainEvent` implements `DomainEventInterface`, not `DomainEvent`. No event class, handler, or infrastructure component references `DomainEvent` — it is an orphaned artifact.

### Affected Files

- `src/Shared/Domain/Event/DomainEvent.php` — delete
- Verify no usages in: `src/`, `tests/`, `config/`

### Acceptance Criteria

- [x] `DomainEvent.php` is deleted
- [x] Grep for `DomainEvent[^I]` (excluding `DomainEventInterface`) returns no PHP source results
- [ ] `make phpstan` passes at level 6 (PHPStan not installed)
- [x] `make test` passes

---

## Task 2: Extend `DoctrineEventStore` deserialization for Transaction events

### Description

`DoctrineEventStore::deserializeEvent()` reconstructs domain events from JSON using reflection, with hardcoded `if`-branches per constructor parameter name (`currency`, `role`, `amount`, `oldEmail`, `newEmail`). When Transaction domain events (`TransactionCreatedEvent`, `TransactionCompletedEvent`, `TransactionFailedEvent`) are dispatched through Messenger (Notification Task 11), their constructor parameters — including `TransactionType` and `TransactionStatus` backed enums — will not deserialize correctly.

The current approach is also fragile: adding any new event with a value-object parameter requires a new `if`-branch in the store. The deserialization logic should be made extensible.

### Affected Files

- `src/Shared/Infrastructure/EventStore/DoctrineEventStore.php` — primary change
- `src/Transaction/Domain/Event/TransactionCreatedEvent.php` — inspect constructor params
- `src/Transaction/Domain/Event/TransactionCompletedEvent.php` — inspect constructor params
- `src/Transaction/Domain/Event/TransactionFailedEvent.php` — inspect constructor params
- `tests/Unit/Shared/Infrastructure/EventStore/DoctrineEventStoreTest.php` — add/extend tests

### Acceptance Criteria

- [x] `deserializeEvent()` correctly reconstructs `TransactionCreatedEvent` (including `TransactionType` enum and `Money` amount)
- [x] `deserializeEvent()` correctly reconstructs `TransactionCompletedEvent`
- [x] `deserializeEvent()` correctly reconstructs `TransactionFailedEvent`
- [x] No hardcoded class names remain in `deserializeEvent()` — deserialization is driven by parameter type reflection or a registered type-map
- [x] Round-trip test: serialize → store → deserialize returns equivalent event for all three Transaction event types
- [x] All existing event types (Account, User) continue to deserialize correctly
- [ ] `make phpstan` passes at level 6 (PHPStan not installed)
- [x] `make test` passes

---

## Task 3: Refactor `AccountRepositoryInterface` to separate write and read concerns

### Description

`AccountRepositoryInterface` mixes three concerns:

1. **Write operations** — `save(Account): void`
2. **Aggregate load operations** — `findById`, `findByUserIdAndCurrency`, `findByUserId` (returns full `Account` entities, needed for command handlers)
3. **Optimized read-model queries** — `getAccountBalance`, `getUserAccountsSummary` (return Application-layer DTOs, needed for query handlers)

The third group violates the Hexagonal rule that domain repository interfaces should not know about Application-layer response types (`AccountBalanceResponse`, `AccountSummary`). It also means `DoctrineAccountRepository` implements read-model logic that belongs in a dedicated read-model port.

The refactoring should extract a separate read-model port interface, following the same pattern already established for the Notification context (`Domain/Port/`).

### Affected Files

**New files**
- `src/Account/Domain/Port/AccountReadModelQuery.php` — new port interface with `getAccountBalance` and `getUserAccountsSummary`

**Modified files**
- `src/Account/Domain/Repository/AccountRepositoryInterface.php` — remove `getAccountBalance` and `getUserAccountsSummary`
- `src/Account/Infrastructure/Repository/DoctrineAccountRepository.php` — implement `AccountReadModelQuery` in addition to `AccountRepositoryInterface`, or extract a separate `DoctrineAccountReadModelQuery` class
- `src/Account/Application/Handler/GetAccountBalanceHandler.php` — inject `AccountReadModelQuery` instead of `AccountRepositoryInterface`
- `src/Account/Application/Handler/GetUserAccountsHandler.php` — inject `AccountReadModelQuery` instead of `AccountRepositoryInterface`
- `src/Account/Application/Handler/EventSourcedGetAccountBalanceHandler.php` — review; may need same change
- `src/Account/Application/Handler/EventSourcedGetUserAccountsHandler.php` — review; may need same change
- `config/services.yaml` — wire new interface to implementation

### Acceptance Criteria

- [ ] `AccountRepositoryInterface` contains only `save`, `findById`, `findByUserIdAndCurrency`, `findByUserId`
- [ ] `AccountReadModelQuery` port interface exists under `src/Account/Domain/Port/`
- [ ] `AccountReadModelQuery` does not import or reference any Application-layer DTO types — DTOs may remain in Application but the port is defined against them
- [ ] All query handlers use `AccountReadModelQuery`, not `AccountRepositoryInterface`
- [ ] All command handlers and `TransferMoneySaga` continue to use `AccountRepositoryInterface`
- [ ] Symfony DI container correctly resolves both interfaces (integration test passes)
- [ ] `make phpstan` passes at level 6
- [ ] `make test` passes
