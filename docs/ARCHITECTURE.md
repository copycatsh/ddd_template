# Architecture Overview

**Stack:** Symfony 7 / PHP 8.3, API Platform, Doctrine ORM, Messenger
**Pattern:** DDD + CQRS + Event Sourcing + Hexagonal Architecture

## Layer Structure (per bounded context)

```
{Context}/
├── Domain/
│   ├── Entity/         # Aggregates
│   ├── ValueObject/    # Immutable value objects
│   ├── Event/          # Domain events
│   ├── Exception/      # Domain exceptions
│   ├── Repository/     # Write repository interfaces (Ports)
│   ├── Port/           # Read-model query interfaces + data DTOs (Ports)
│   ├── Service/        # Domain services (pure domain logic)
│   ├── Specification/  # Composable business rules
│   └── Policy/         # Enforceable business policies
├── Application/
│   ├── Command/        # Write-side commands
│   ├── Handler/        # Command & query handlers
│   ├── Query/          # Read-side queries + response DTOs
│   └── Saga/           # Multi-step orchestrations
└── Infrastructure/
    ├── Repository/     # Doctrine implementations (Adapters)
    ├── Console/        # Symfony Console commands (CLI driving adapters)
    ├── Security/       # Security infrastructure adapters
    ├── Query/          # DBAL query adapters for domain ports
    ├── Projection/     # Event-driven read model projections
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

## Implementation Pattern

- **Account context** — Event Sourced. `Account` aggregate with state reconstructed by replaying events from `event_store` table. Read queries use `account_projections` table for O(1) lookups.
- **User context** — CRUD only. `User` entity with Doctrine ORM. Domain events (`UserCreatedEvent`, `UserEmailChangedEvent`) dispatched via `DomainEventsTrait` and Messenger.
- **Transaction context** — CRUD only (`Transaction`, `DoctrineTransactionRepository`). Not an ES aggregate.
- **Notification context** — CRUD only (`NotificationLog`). Consumes Integration Events from Transaction BC.

## Event Sourcing (Account BC)

`AbstractAggregateRoot` (`src/Shared/Domain/Aggregate/`) is the base for event-sourced aggregates:
1. Domain method calls `$this->recordEvent(new SomeDomainEvent(...))`
2. `recordEvent` stores the event via `DomainEventsTrait`, then calls `apply{EventClassName}()` to mutate state
3. Repository saves pending events via `DoctrineEventStore` and dispatches via Messenger

## Domain Events for CRUD Entities (User BC)

`DomainEventsTrait` (`src/Shared/Domain/Event/`) provides event collection for non-ES entities:
1. Entity calls `$this->recordEvent(new SomeDomainEvent(...))`
2. Events accumulate until repository save
3. Repository dispatches events via Messenger inside a DBAL transaction, then calls `markEventsAsCommitted()`

---

## Bounded Context: Account

**Entities** (`Domain/Entity/`)
- `Account` — ES aggregate extending `AbstractAggregateRoot`. State rebuilt by replaying domain events. Enforces deposit/withdraw rules via bcmath, positive-amount guards, currency matching, and insufficient funds checks.

**Value Objects** — `Money` and `Currency` live in Shared kernel (`src/Shared/Domain/ValueObject/`)

**Domain Events** (`Domain/Event/`)
- `AccountCreatedEvent` — `accountId`, `userId`, `Currency`
- `MoneyDepositedEvent` — `accountId`, `Money`, `newBalance`
- `MoneyWithdrawnEvent` — `accountId`, `Money`, `newBalance`

**Domain Exceptions** (`Domain/Exception/`)
- `AccountAlreadyExistsException` → 409
- `AccountNotFoundException` → 404
- `InsufficientFundsException` → 400

Note: `InvalidAmountException`, `NegativeBalanceException`, and `CurrencyMismatchException` are in Shared kernel (`src/Shared/Domain/Exception/`).

**Repository Interfaces** (`Domain/Repository/`)
- `AccountRepositoryInterface` — `save`, `findById`

**Commands** (`Application/Command/`)
- `CreateAccountCommand`, `DepositMoneyCommand`, `WithdrawMoneyCommand`, `TransferMoneyCommand`

**Queries** (`Application/Query/`)
- `GetAccountBalanceQuery` → `AccountBalanceResponse`
- `GetUserAccountsQuery` → `UserAccountsResponse` (wraps `AccountSummary[]`)
- `GetAccountTransactionsQuery` → `TransactionDto[]`

**Handlers** (`Application/Handler/`)

| Handler | Type | Description |
|---|---|---|
| `CreateAccountHandler` | Command | Creates ES account, checks uniqueness via projection |
| `DepositMoneyHandler` | Command | Deposits via ES aggregate |
| `WithdrawMoneyHandler` | Command | Withdraws via ES aggregate |
| `GetAccountBalanceHandler` | Query | Reads from projection table |
| `GetUserAccountsHandler` | Query | Lists user's accounts from projection table |
| `GetAccountTransactionsHandler` | Query | Reads from Transaction context (DBAL) |

**Sagas** (`Application/Saga/`)
- `TransferMoneySaga` — pure orchestrator for fund transfers. Delegates business rule validation to `MoneyTransferDomainService`, then executes: creates Transaction (PENDING), withdraws from source, deposits to destination, marks COMPLETED. Dispatches Integration Events via `IntegrationEventMapper`. All saves wrapped in a single DBAL transaction for ACID guarantees.

**Domain Services** (`Domain/Service/`)
- `MoneyTransferDomainService` — validates transfer eligibility via Specification composite + Policy enforcement. Pure domain, no infrastructure dependencies.

**Specifications** (`Domain/Specification/`)
- `SpecificationInterface<T>` — composable business rule with `isSatisfiedBy(T): bool`, `reason(): string`, and `and()/or()/not()` operators
- `AbstractSpecification<T>` — base class providing `and()/or()/not()` composition methods
- `AndSpecification`, `OrSpecification`, `NotSpecification` — boolean composites
- `Transfer/TransferRequest` — DTO carrying transfer context (account IDs, currencies, amount)
- `Transfer/NotSameAccountSpecification` — rejects same-account transfers
- `Transfer/CurrencyMatchSpecification` — rejects cross-currency transfers
- `Transfer/AmountCurrencyMatchSpecification` — rejects amount/account currency mismatch

**Policies** (`Domain/Policy/`)
- `TransferLimitPolicyInterface` — contract: `enforce(accountId, amount): void` (throws on violation)
- `TransferLimitPolicy` — enforces daily transfer limit per account. Reads transfer history via `TransferActivityQuery` port. UTC timezone, configurable limit.

**Ports** (`Domain/Port/`)
- `AccountProjectionQuery` / `AccountProjectionData` — read-model queries for account projections (O(1) via `account_projections` table)
- `TransferActivityQuery` / `TransferActivityData` — read-only access to daily transfer activity for policy enforcement

**Infrastructure** (`Infrastructure/`)
- `AccountRepository` — wraps `EventStoreInterface`; persists and reconstitutes aggregates from event store. Dispatches events via Messenger inside DBAL transaction.
- `Query/DoctrineTransferActivityQuery` — DBAL adapter for `TransferActivityQuery` port. Queries COMPLETED transfers from transactions table.
- API Platform resource: `AccountResource` — DTO with `#[ApiResource]` annotations defining all routes
- API Platform processors: `CreateAccount`, `DepositMoney`, `WithdrawMoney`, `TransferMoney` — call ES handlers, return `AccountResource`
- API Platform providers: `AccountBalance`, `UserAccounts`, `AccountTransactions`
- DTOs: `CreateAccountDto`, `MoneyOperationDto`, `TransferMoneyDto`

**Console commands** (`Infrastructure/Console/`)
- `app:deposit-money`, `app:withdraw-money`, `app:transfer-money`
- `app:get-account-balance`, `app:get-user-accounts`, `app:account:transactions`
- `app:rebuild-account-projections`

**Projections** (`Infrastructure/Projection/`)
- `AccountProjectionHandler` — Messenger handler that updates `account_projections` table on `AccountCreated/MoneyDeposited/MoneyWithdrawn` events (sync transport, atomic with event store)
- `DoctrineAccountProjectionQuery` — DBAL reads from projection table for O(1) account queries
- Read queries (`GetAccountBalanceHandler`, `GetUserAccountsHandler`) use projection table
- `CreateAccountHandler` uses projection for duplicate check (UNIQUE index on user_id + currency)

---

## Bounded Context: User

**Entities** (`Domain/Entity/`)
- `User` — CRUD entity, Doctrine ORM. Implements Symfony `UserInterface` + `PasswordAuthenticatedUserInterface`. Uses `DomainEventsTrait` for domain event collection. Private constructor; created via `User::create()` static factory which records `UserCreatedEvent`. `changeEmail(Email)` records `UserEmailChangedEvent`.

**Value Objects** (`Domain/ValueObject/`)
- `Email` — `final readonly`; validates via `FILTER_VALIDATE_EMAIL`, normalises to lowercase
- `UserRole` — backed enum: `USER = 'ROLE_USER'`, `ADMIN = 'ROLE_ADMIN'`

**Domain Events** (`Domain/Event/`)
- `UserCreatedEvent` — `userId`, `email`, `UserRole`
- `UserEmailChangedEvent` — `userId`, `oldEmail`, `newEmail`

Events are dispatched via `DoctrineUserRepository` using Messenger (sync transport).

**Domain Exceptions** (`Domain/Exception/`)
- `UserAlreadyExistsException` → 409
- `UserNotFoundException` → 404
- `InvalidCredentialsException` → 401

**Ports** (`Domain/Port/`)
- `PasswordHasherInterface` — domain port for password hashing (`hash(string): string`). Infrastructure adapter: `SymfonyPasswordHasher` wraps Symfony's `PasswordHasherFactoryInterface`.

**Repository Interfaces** (`Domain/Repository/`)
- `UserRepositoryInterface` — `save`, `findById`, `findByEmail`, `delete`

**Commands** (`Application/Command/`)
- `CreateUserCommand` — `email`, `password`, `UserRole`
- `ChangeUserEmailCommand` — `userId`, `Email`
- `DeleteUserCommand` — `userId`

**Handlers** (`Application/Handler/`)
- `CreateUserHandler` — hashes password via `PasswordHasherInterface` domain port, creates user via `User::create()` factory
- `ChangeUserEmailHandler` — validates user exists + email unique, delegates to `User::changeEmail()`
- `DeleteUserHandler` — validates user exists, delegates to `UserRepositoryInterface::delete()`

**Infrastructure** (`Infrastructure/Repository/`)
- `DoctrineUserRepository` — persists via Doctrine ORM, dispatches domain events via `MessageBusInterface` inside DBAL transaction

**Infrastructure** (`Infrastructure/Security/`)
- `SymfonyPasswordHasher` — adapter implementing `PasswordHasherInterface`, wraps `PasswordHasherFactoryInterface`

**Infrastructure** (`Infrastructure/ApiPlatform/`)
- `Resource/UserResource` — output DTO with `fromUser()` factory
- `Dto/CreateUserDto` — POST `/api/users` input (email, password, role)
- `Dto/ChangeUserEmailDto` — PUT `/api/users/{id}/email` input (email)
- `StateProcessor/CreateUserStateProcessor`, `ChangeUserEmailStateProcessor`, `DeleteUserStateProcessor`
- `StateProvider/GetUserStateProvider`
- Delete endpoint guards against orphaned accounts via `GetUserAccountsHandler`

**Console commands** (`Infrastructure/Console/`)
- `app:create-user`, `app:user:change-email`, `app:user:info`

---

## Bounded Context: Transaction

**Entities** (`Domain/Entity/`)
- `Transaction` — CRUD aggregate. Fields: `id`, `fromAccountId`, `toAccountId?`, `TransactionType`, `amount`, `currency`, `TransactionStatus`, `createdAt`, `completedAt?`. Methods: `complete()`, `fail()` guard against double-completion.

**Value Objects** (`Domain/ValueObject/`)
- `TransactionStatus` — backed enum: `PENDING`, `COMPLETED`, `FAILED`
- `TransactionType` — backed enum: `DEPOSIT`, `WITHDRAWAL`, `TRANSFER`

**Domain Events** (`Domain/Event/`)
> These events are internal to Transaction BC. Cross-BC consumers receive
> Integration Events (`Shared/Integration/Event/`) via IntegrationEventMapper.

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

> Consumes **Integration Events** (`Shared/Integration/Event/`),
> not Transaction domain events. This decouples Notification from Transaction BC.

**Entities** (`Domain/Entity/`)
- `NotificationLog` — Doctrine ORM entity (`notification_log` table). Records sent notifications: `transactionId`, `accountId`, `userId`, `recipientEmail`, `notificationType`, `sentAt`.

**Value Objects** (`Domain/ValueObject/`)
- `NotificationType` — backed enum for notification types

**Domain Exceptions** (`Domain/Exception/`)
- `NotificationException`

**Repository Interfaces** (`Domain/Repository/`)
- `NotificationLogRepositoryInterface` — `save(NotificationLog): void`

**Query Ports** (`Domain/Port/`)
Anti-corruption layer — read-only cross-context access without depending on User/Account domain models.
- `NotificationUserQuery` / `NotificationUserData` (`userId`, `email`)
- `NotificationAccountQuery` / `NotificationAccountData` (`accountId`, `userId`, `currency`)
- `NotificationHistoryQuery` / `NotificationHistoryData` — read-only notification history

**Handlers** (`Application/Handler/`)
- `TransactionCreatedNotificationHandler` — handles `TransactionCreatedIntegrationEvent`
- `TransactionCompletedNotificationHandler` — handles `TransactionCompletedIntegrationEvent`
- `TransactionFailedNotificationHandler` — handles `TransactionFailedIntegrationEvent`
- `GetNotificationHistoryHandler` — query handler

**Queries** (`Application/Query/`)
- `GetNotificationHistoryQuery` → `NotificationHistoryResponse` (wraps `NotificationHistoryItem[]`)

**Infrastructure** (`Infrastructure/`)
- `DoctrineNotificationLogRepository` — Doctrine ORM adapter
- `Query/DoctrineNotificationUserQuery` — raw DBAL, no User domain dependency
- `Query/DoctrineNotificationAccountQuery` — raw DBAL, no Account domain dependency
- `Query/DoctrineNotificationHistoryQuery` — DBAL adapter for `NotificationHistoryQuery` port
- `ApiPlatform/StateProvider/NotificationHistoryStateProvider` — API endpoint for notification history
- `ApiPlatform/Dto/NotificationHistoryDto` — output DTO

---

## Integration Events (Published Language)

Integration events decouple cross-BC communication from domain models.

**Integration Events** (`Shared/Integration/Event/`)
- `TransactionCreatedIntegrationEvent` — `transactionId`, `accountId`, `amount`, `currency`
- `TransactionCompletedIntegrationEvent` — `transactionId`, `accountId`
- `TransactionFailedIntegrationEvent` — `transactionId`, `accountId`, `reason?`

**Mapper** (`Shared/Integration/`)
- `IntegrationEventMapperInterface` / `IntegrationEventMapper` — translates Transaction domain events to integration event DTOs

**Flow:** TransferMoneySaga calls `IntegrationEventMapper.map()` with domain events → integration event DTOs → Messenger dispatch (sync) → Notification handlers

Domain Events (Transaction BC internal) vs Integration Events (cross-BC public contract). See ADR 004 for rationale.

---

## Shared Kernel

**Aggregate base** (`Domain/Aggregate/`)
- `AggregateRootInterface` — contract: `getId`, `getVersion`, `getUncommittedEvents`, `markEventsAsCommitted`, `applyEvent`
- `AbstractAggregateRoot` — uses `DomainEventsTrait` for event collection. Overrides `recordEvent()` to also call `apply{EventClassName}()` via reflection. `reconstitute()` replays events without constructor.

**Domain event infrastructure** (`Domain/Event/`)
- `DomainEventInterface` — contract: `getAggregateId`, `getEventType`, `getOccurredAt`, `getEventData`, `getVersion`
- `AbstractDomainEvent` — base class implementing `DomainEventInterface`. Sets `occurredAt = now()`, `version = 1`. Subclasses implement `getAggregateId()` and `getEventData()`.
- `DomainEventsTrait` — reusable trait for domain event collection. Provides `recordEvent()`, `getUncommittedEvents()`, `markEventsAsCommitted()`. Used by both `AbstractAggregateRoot` (ES) and `User` entity (CRUD).

**Value Objects** (`Domain/ValueObject/`)
- `Currency` — backed enum (`UAH`, `USD`) with equality helper
- `Money` — immutable; wraps `string $amount` + `Currency`. Guards negative amounts and currency mismatches.

**Domain exceptions** (`Domain/Exception/`)
- `DomainException` — abstract base extending PHP `\DomainException`
- `InvalidAmountException` → 400 (negative amount, non-positive operation amount)
- `NegativeBalanceException` → 400 (subtraction would result in negative)
- `CurrencyMismatchException` → 400 (operation currency doesn't match account currency)

**Infrastructure** (`Infrastructure/EventStore/`)
- `EventStoreInterface` — `saveEvents`, `getEventsForAggregate`, `getEventsForAggregateFromVersion`, `getAllEvents`, `getEventsByType`
- `DoctrineEventStore` — DBAL implementation. Stores events in `event_store` table. Enforces optimistic concurrency via version check. Deserializes events via reflection-based type resolution (handles value objects, backed enums, and scalars automatically).

---

## Cross-cutting Infrastructure

- `DomainExceptionSubscriber` — maps domain exceptions to HTTP responses (`kernel.exception`, priority 10)
- `OpenApiJwtDecorator` — injects JWT bearer security scheme into OpenAPI docs

---

## UI Layer (`src/UI/`)

Minimal presentation-layer entry points outside bounded contexts.

**`src/UI/Http/`** — HTTP controllers:
- `HealthController` — `GET /health`, `GET /`

> Console commands live inside each BC's `Infrastructure/Console/` directory (not in `src/UI/`).
> API endpoints are defined via API Platform in each BC's `Infrastructure/ApiPlatform/`.

**`src/DataFixtures/`** — Doctrine fixtures: `UserFixtures`, `AccountFixtures`, `AppFixtures`

---

## Architectural Notes

1. **Transaction events dispatched via Integration Events** — `TransferMoneySaga` creates Transaction domain events, maps them to Integration Events via `IntegrationEventMapper`, and dispatches via Messenger. Notification handlers consume the Integration Events.
2. **Account context is ES-only** — CRUD Account entity and handlers were removed in Phase 2.5. All Account operations go through the event store.
3. **User context is CRUD-only** — ES User entity and handlers were removed in Phase 6. Domain events are dispatched via `DomainEventsTrait` + `DoctrineUserRepository`.
4. **ES read performance** — Account reads use projections (`account_projections` table) for O(1) queries. Projections are updated synchronously on every event via Messenger. Use `app:rebuild-account-projections` to rebuild from event store.
5. **Messenger configuration** — All event routing uses sync transport. Bus configured with `allow_no_handlers: true` to support events without subscribers yet.
