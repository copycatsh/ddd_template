# ddd_template — Roadmap

## Completed

- ✅ Phase 1: Cleanup + Aggregate Factory (factory methods on aggregates)
- ✅ Phase 2.5: ES Migration (Account BC fully event-sourced)
- ✅ Phase 2.6: UI structure (hexagonal driving adapters)
- ✅ Phase 3: ES Projections (account_projections table, O(1) reads)
- ✅ Phase 3.1: Domain Refinements (exception hierarchy, DomainException per BC)
- ✅ Phase 7: Shared Kernel (Money/Currency VOs + CurrencyMismatchException → Shared kernel)

---

## Phase 4: Domain Services + Specification + Policy

### MoneyTransferDomainService
Extract business rules from TransferMoneySaga into Domain layer.
Saga becomes pure orchestrator: load accounts → call domain service → save → dispatch events.

Location: src/Account/Domain/Service/MoneyTransferDomainService.php

Rules to move from TransferMoneySaga:
- same-account transfer check
- currency match between accounts
- amount currency match

### TransferLimitPolicy
Policy Pattern — business rule for transfer limits.
Example: daily transfer limit per account.

Location: src/Account/Domain/Policy/TransferLimitPolicy.php

Teaches: difference between Domain Service (coordinates aggregates)
and Policy (encapsulates a business rule/decision).

### AccountSpecification
Specification Pattern — composable business rules for account eligibility.
Example: AccountCanReceiveTransferSpecification.

Location: src/Account/Domain/Specification/AccountSpecification.php

Teaches: how Specification differs from validation (reusable, composable,
expressible in domain language).

ADR: docs/architecture/decisions/003-domain-services-patterns.md

---

## Phase 5: Integration Events

### Separate Domain Events from Integration Events
Problem: TransactionCreated/Completed/FailedEvent are Domain Events
but dispatched via Messenger to Notification BC as Integration Events — violation.
Notification BC currently depends on Transaction domain model.

What to add:
- src/Shared/Infrastructure/Integration/Event/TransactionCreatedIntegrationEvent.php
- src/Shared/Infrastructure/Integration/Event/TransactionCompletedIntegrationEvent.php
- src/Shared/Infrastructure/Integration/Event/TransactionFailedIntegrationEvent.php
- Mapper: DomainEvent → IntegrationEvent in Infrastructure layer

Teaches: Domain Events (internal, past tense, domain language) vs
Integration Events (public contract, cross-BC, stable interface).
Published Language strategic pattern implemented here.

ADR: docs/architecture/decisions/004-integration-events-separation.md

---

## Phase 6: User BC completion

### Delete ES dead code
User BC stays CRUD permanently. Account BC is the ES showcase.

Remove:
- src/User/Domain/Entity/EventSourcedUser.php
- src/User/Domain/Repository/EventSourcedUserRepositoryInterface.php
- src/User/Infrastructure/Repository/EventSourcedUserRepository.php
- src/User/Application/Handler/EventSourcedCreateUserHandler.php
- src/User/Application/Handler/EventSourcedChangeUserEmailHandler.php
- src/UI/Console/CreateUserEventSourcedConsoleCommand.php

### HTTP API endpoints for User BC
Pattern: follow Notification BC (Infrastructure/ApiPlatform/Dto + StateProcessor + StateProvider)

Endpoints:
- POST   /api/users              — create user
- GET    /api/users/{id}         — get user profile
- PUT    /api/users/{id}/email   — change email
- DELETE /api/users/{id}         — delete user

### Distribute console commands to bounded contexts
Move from src/UI/Console/ into owning BC Infrastructure/Console/.
Cross-context commands (e.g. GetUserInfoConsoleCommand) stay in src/UI/Console/.

### Smoke tests
Integration tests:
- All console commands — exit code 0 with valid args
- HealthController endpoints — HTTP 200

---

## Phase 7+: Microservices prep (deferred)

Implement when splitting BCs into separate services:
- Outbox Pattern (at-least-once delivery across DB boundaries)
- Full Saga Pattern (state machine: PENDING→WITHDRAWING→DEPOSITING→COMPLETING→FAILED,
  compensating transactions, idempotency keys)
- CDC (Change Data Capture)
- Microservices split: account-service, user-service, transaction-service, notification-service