# Architecture Overview

**Stack:** Symfony 7 / PHP 8.3, API Platform, Doctrine ORM, Messenger
**Pattern:** DDD + CQRS + Event Sourcing + Hexagonal Architecture

## Layer Structure (per bounded context)

```
{Context}/
├── Domain/
│   ├── Entity/         # Aggregates (CRUD + EventSourced variants)
│   ├── ValueObject/    # Immutable value objects
│   ├── Event/          # Domain events
│   ├── Exception/      # Domain exceptions
│   ├── Repository/     # Write repository interfaces (Ports)
│   └── Port/           # Read-model query interfaces (Ports)
├── Application/
│   ├── Command/        # Write-side commands
│   ├── Handler/        # Command & query handlers
│   ├── Query/          # Read-side queries + response DTOs
│   └── Saga/           # Multi-step orchestrations
└── Infrastructure/
    ├── Repository/     # Doctrine implementations (Adapters)
    └── ApiPlatform/    # State Processors (commands) + State Providers (queries) + DTOs
```

## API Flow (CQRS via API Platform)

```
HTTP → State Processor → Command → Handler → Aggregate → Repository (write)
HTTP → State Provider  → Query   → Handler → ReadModelQuery (read)
```

Domain exceptions are mapped to HTTP responses by `DomainExceptionSubscriber` (`src/Infrastructure/EventSubscriber/`).

## Key Domain Rules

- Money arithmetic uses `bcmath` (string-based, 2 decimal precision)
- One account per user per currency
- No negative balances; no negative Money amounts
- Currency must match for all operations on an account

## Dual Implementation Pattern

Each aggregate exists in two forms:
- **CRUD** (`Account`, `DoctrineAccountRepository`) — standard Doctrine ORM
- **Event Sourced** (`EventSourcedAccount`, `EventSourcedAccountRepository`) — state reconstructed by replaying events from `event_store` table

Both are fully wired. The event-sourced handlers are named `EventSourced*Handler`. Active API routes use CRUD; ES handlers are wired but not exposed via HTTP.

## Event Sourcing

`AbstractAggregateRoot` (`src/Shared/Domain/Aggregate/`) is the base for event-sourced aggregates:
1. Domain method calls `$this->recordEvent(new SomeDomainEvent(...))`
2. `recordEvent` immediately calls `apply{EventClassName}()` to mutate state
3. Repository saves pending events via `DoctrineEventStore`

---

## Bounded Context: Account

**Entities** (`Domain/Entity/`)
- `Account` — CRUD aggregate, Doctrine ORM entity. Holds balance, currency, userId. Enforces deposit/withdraw rules via bcmath.
- `EventSourcedAccount` — ES aggregate extending `AbstractAggregateRoot`. State rebuilt by replaying domain events.

**Value Objects** (`Domain/ValueObject/`)
- `Currency` — backed enum (`UAH`, `USD`) with equality helper
- `Money` — immutable; wraps `string $amount` + `Currency`. Guards negative amounts and currency mismatches.

**Domain Events** (`Domain/Event/`)
- `AccountCreatedEvent` — `accountId`, `userId`, `Currency`
- `MoneyDepositedEvent` — `accountId`, `Money`, `newBalance`
- `MoneyWithdrawnEvent` — `accountId`, `Money`, `newBalance`

**Domain Exceptions** (`Domain/Exception/`)
- `AccountAlreadyExistsException` → 409
- `AccountNotFoundException` → 404
- `CurrencyMismatchException` → 400
- `InsufficientFundsException` → 400
- `InvalidAmountException` → 400

**Repository Interfaces** (`Domain/Repository/`)
- `AccountRepositoryInterface` — `save`, `findById`, `findByUserIdAndCurrency`, `findByUserId`
- `EventSourcedAccountRepositoryInterface` — `save`, `findById`, `findByUserIdAndCurrency`, `findByUserId`

**Read-Model Ports** (`Domain/Port/`)
- `AccountReadModelQuery` — `getAccountBalance`, `getUserAccountsSummary` (returns `AccountBalanceData`, `AccountSummaryData`)
- `AccountBalanceData`, `AccountSummaryData` — port DTOs (domain layer, no Application imports)

**Commands** (`Application/Command/`)
- `CreateAccountCommand`, `DepositMoneyCommand`, `WithdrawMoneyCommand`, `TransferMoneyCommand`

**Queries** (`Application/Query/`)
- `GetAccountBalanceQuery` → `AccountBalanceResponse`
- `GetUserAccountsQuery` → `UserAccountsResponse` (wraps `AccountSummary[]`)
- `GetAccountTransactionsQuery` → `TransactionDto[]`

**Handlers** (`Application/Handler/`)

| Handler | Variant |
|---|---|
| `CreateAccountHandler` | CRUD |
| `DepositMoneyHandler` | CRUD |
| `WithdrawMoneyHandler` | CRUD |
| `TransferMoneyHandler` | CRUD — delegates to `TransferMoneySaga` |
| `GetAccountBalanceHandler` | CRUD |
| `GetUserAccountsHandler` | CRUD |
| `GetAccountTransactionsHandler` | CRUD |
| `EventSourcedCreate/Deposit/Withdraw/GetBalance/GetUserAccountsHandler` | ES variants |

**Sagas** (`Application/Saga/`)
- `TransferMoneySaga` — orchestrates fund transfer: creates `Transaction` (PENDING) → withdraws → deposits → marks COMPLETED, all inside a Doctrine DB transaction. Rolls back on any failure.

**Infrastructure** (`Infrastructure/`)
- `DoctrineAccountRepository` — implements `AccountRepositoryInterface` (write + find)
- `DoctrineAccountReadModelQuery` — implements `AccountReadModelQuery` (read-model queries)
- `EventSourcedAccountRepository` — wraps `EventStoreInterface`; scan-based `findByUserId`
- API Platform processors: `CreateAccount`, `DepositMoney`, `WithdrawMoney`, `TransferMoney`
- API Platform providers: `AccountBalance`, `UserAccounts`, `AccountTransactions`
- DTOs: `CreateAccountDto`, `MoneyOperationDto`, `TransferMoneyDto`

---

## Bounded Context: User

**Entities** (`Domain/Entity/`)
- `User` — CRUD aggregate, Doctrine ORM entity. Implements Symfony `UserInterface` + `PasswordAuthenticatedUserInterface`.
- `EventSourcedUser` — ES aggregate. Supports `create()` and `changeEmail()`.

**Value Objects** (`Domain/ValueObject/`)
- `Email` — `final readonly`; validates via `FILTER_VALIDATE_EMAIL`, normalises to lowercase
- `UserRole` — backed enum: `USER = 'ROLE_USER'`, `ADMIN = 'ROLE_ADMIN'`

**Domain Events** (`Domain/Event/`)
- `UserCreatedEvent` — `userId`, `email`, `hashedPassword`, `UserRole`
- `UserEmailChangedEvent` — `userId`, `oldEmail`, `newEmail`

**Domain Exceptions** (`Domain/Exception/`)
- `UserAlreadyExistsException` → 409
- `InvalidCredentialsException` → 401

**Repository Interfaces** (`Domain/Repository/`)
- `UserRepositoryInterface` — `save`, `findById`, `findByEmail`, `delete`
- `EventSourcedUserRepositoryInterface` — `save`, `findById`, `findByEmail`

**Commands** (`Application/Command/`)
- `CreateUserCommand` — `email`, `password`, `UserRole`
- `ChangeUserEmailCommand` — `userId`, `Email`

**Handlers** (`Application/Handler/`)
- `CreateUserHandler` — CRUD; hashes password via `UserPasswordHasherInterface`
- `EventSourcedCreateUserHandler` — ES
- `EventSourcedChangeUserEmailHandler` — ES

**Infrastructure** (`Infrastructure/Repository/`)
- `DoctrineUserRepository`, `EventSourcedUserRepository`

---

## Bounded Context: Transaction

**Entities** (`Domain/Entity/`)
- `Transaction` — CRUD aggregate. Fields: `id`, `fromAccountId`, `toAccountId?`, `TransactionType`, `amount`, `currency`, `TransactionStatus`, `createdAt`, `completedAt?`. Methods: `complete()`, `fail()` guard against double-completion.

**Value Objects** (`Domain/ValueObject/`)
- `TransactionStatus` — backed enum: `PENDING`, `COMPLETED`, `FAILED`
- `TransactionType` — backed enum: `DEPOSIT`, `WITHDRAWAL`, `TRANSFER`

**Domain Events** (`Domain/Event/`)
> These exist but are **never dispatched**. Wiring them through Messenger is pending (Notification context Task 11).

- `TransactionCreatedEvent` — `transactionId`, `accountId`, `TransactionType`, `amount`, `currency`
- `TransactionCompletedEvent` — `transactionId`, `accountId`
- `TransactionFailedEvent` — `transactionId`, `accountId`, `reason?`

**Domain Exceptions** (`Domain/Exception/`)
- `TransactionAlreadyCompletedException`
- `TransactionNotFoundException`

**Repository Interfaces** (`Domain/Repository/`)
- `TransactionRepositoryInterface` — `save`, `findById`, `findByAccountId`, `findByStatus`, `findPendingByAccountId`, `delete`

> No Application layer — write operations are driven by `Account`'s `TransferMoneySaga`.

**Infrastructure** (`Infrastructure/Repository/`)
- `DoctrineTransactionRepository` — queries both `fromAccountId` and `toAccountId`; orders by `createdAt DESC`

---

## Bounded Context: Notification

> **Status:** Domain layer only. Application and Infrastructure layers pending (Tasks 4–12).
> **Branch:** `feature/notification-bounded-context` (worktree: `.worktrees/notification`)

**Entities** (`Domain/Entity/`)
- `NotificationLog` — Doctrine ORM entity (`notification_log` table). Records sent notifications: `transactionId`, `accountId`, `userId`, `recipientEmail`, `notificationType`, `sentAt`.

**Repository Interfaces** (`Domain/Repository/`)
- `NotificationLogRepositoryInterface` — `save(NotificationLog): void`

**Query Ports** (`Domain/Port/`)
Anti-corruption layer — read-only cross-context access without depending on User/Account domain models.
- `NotificationUserQuery` / `NotificationUserData` (`userId`, `email`)
- `NotificationAccountQuery` / `NotificationAccountData` (`accountId`, `userId`, `currency`)

---

## Shared Kernel

**Aggregate base** (`Domain/Aggregate/`)
- `AggregateRootInterface` — contract: `getId`, `getVersion`, `getUncommittedEvents`, `markEventsAsCommitted`, `applyEvent`
- `AbstractAggregateRoot` — `recordEvent()` appends to `$uncommittedEvents` and calls `apply{EventClassName}()` via reflection. `reconstitute()` replays events without constructor.

**Domain event base** (`Domain/Event/`)
- `DomainEventInterface` — contract: `getAggregateId`, `getEventType`, `getOccurredAt`, `getEventData`, `getVersion`
- `AbstractDomainEvent` — base class implementing `DomainEventInterface`. Sets `occurredAt = now()`, `version = 1`. Subclasses implement `getAggregateId()` and `getEventData()`.

**Domain exception base** (`Domain/Exception/`)
- `DomainException` — abstract base extending PHP `\DomainException`

**Infrastructure** (`Infrastructure/EventStore/`)
- `EventStoreInterface` — `saveEvents`, `getEventsForAggregate`, `getEventsForAggregateFromVersion`, `getAllEvents`, `getEventsByType`
- `DoctrineEventStore` — DBAL implementation. Stores events in `event_store` table. Enforces optimistic concurrency via version check. Deserializes events via reflection-based type resolution (handles value objects, backed enums, and scalars automatically).

---

## Cross-cutting Infrastructure

- `DomainExceptionSubscriber` — maps domain exceptions to HTTP responses (`kernel.exception`, priority 10)
- `OpenApiJwtDecorator` — injects JWT bearer security scheme into OpenAPI docs
- `HealthController` — `GET /health`, `GET /`

---

## Legacy / Non-DDD Code

**`src/Command/`** — Symfony Console commands wrapping DDD handlers (CLI entry points):
`CreateUser`, `CreateUserEventSourced`, `ChangeUserEmail`, `DepositMoney`, `WithdrawMoney`, `TransferMoney`, `GetAccountBalance`, `GetUserAccounts`, `GetAccountTransactions`, `GetUserInfo`, `TestEventSourcing`

**`src/DataFixtures/`** — Doctrine fixtures: `UserFixtures`, `AccountFixtures`, `AppFixtures`

---

## Architectural Notes

1. **Transaction events not dispatched** — `TransactionCreated/Completed/FailedEvent` exist in the domain but `TransferMoneySaga` uses a plain DB transaction. Messenger dispatch is pending (Notification Task 11).
