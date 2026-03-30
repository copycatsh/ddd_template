# TODOS

## Phase 5: Outbox Pattern

### Transactional Outbox for Event Publishing

**What:** Replace sync Messenger dispatch in `AccountRepository.save()` with the Outbox Pattern: store events in an `outbox` table (same DBAL transaction as event store), then publish asynchronously via a background worker.

**Why:** Sync dispatch works for single-DB but won't survive a microservices split. The outbox pattern guarantees at-least-once delivery across DB boundaries.

**Pros:** Crash-safe, works across DB boundaries, enables async projections.

**Cons:** Adds eventual consistency, infrastructure complexity (message broker, outbox worker).

**Context:** Current sync approach is correct for this template's single-DB setup. See TODO comment in `AccountRepository.save()`.

**Depends on:** Phase 4a (projections) — completed

## Phase 6: Full Saga Pattern

### Saga state machine with compensating transactions

**What:** Evolve `TransferMoneySaga` toward a microservices-ready saga pattern with explicit saga state machine, idempotency keys, and compensation steps as first-class concepts.

**Why:** The current saga uses DBAL transaction wrapping for ACID guarantees (correct for single-DB). In a distributed system with separate databases per bounded context, you need compensating events instead. A full saga pattern has explicit states (PENDING, WITHDRAWING, DEPOSITING, COMPLETING, COMPENSATING, FAILED) and can resume from any state after a crash.

**Pros:** Crash-resilient, resumable, production-grade pattern for distributed systems.

**Cons:** Significant complexity. Currently overkill for a single-DB learning template.

**Context:** Current implementation handles the happy path with DBAL transaction wrapping and documents where compensation would be needed in a distributed system (code comments in TransferMoneySaga). Full saga adds state persistence and resumability.

**Depends on:** Phase 2.5 (ES migration) — completed

## UI Layer

### Smoke tests for console commands and HealthController

**What:** Integration tests that verify each of the 10 console commands runs without error (exit code 0 with valid args) and HealthController endpoints return 200.

**Why:** All CLI and HTTP entry points have zero test coverage. The underlying CQRS handlers are fully tested, but DI wiring and argument parsing are not. A misconfigured service binding would only surface at runtime.

**Cons:** Requires Symfony kernel boot in tests (integration test setup).

**Context:** These are thin wrappers around CQRS handlers. Risk is low but not zero.

**Depends on:** None

### Distribute console commands to bounded contexts

**What:** Move each console command from `src/UI/Console/` into its owning bounded context's `Infrastructure/Console/` directory (e.g., `DepositMoneyConsoleCommand` → `Account/Infrastructure/Console/`).

**Why:** API Platform processors already live inside bounded contexts. Console commands should follow the same pattern for hexagonal architecture consistency.

**Cons:** Cross-context commands (e.g., `GetUserInfoConsoleCommand` reads User+Account data) need a home — likely stays in `UI/Console/` or goes to a shared query namespace.

**Context:** The flat `UI/Console/` grouping was chosen as the first step. Distribution is a follow-up architectural decision.

**Depends on:** UI layer restructuring — completed

## Shared Kernel

### Move Money/Currency VOs to Shared kernel

**What:** Move `Money` and `Currency` value objects from `Account/Domain/ValueObject/` to `Shared/Domain/ValueObject/`. Update all imports across bounded contexts.

**Why:** Money and Currency are used across multiple BCs (Account, Transaction). They currently live in Account BC, creating a cross-BC dependency. Shared kernel is the correct location for cross-BC value objects.

**Cons:** Large blast radius — every file importing Money/Currency needs an import update. Also requires moving `CurrencyMismatchException` to Shared (it depends on Currency VO).

**Context:** Deferred from the domain refinements PR to keep scope manageable. When moved, `CurrencyMismatchException` can also move to Shared kernel.

**Depends on:** None

### Rename EventSourcedUserRepositoryInterface → UserRepositoryInterface

**What:** Rename `EventSourcedUserRepositoryInterface` to `UserRepositoryInterface` and `EventSourcedUserRepository` to `UserRepository` in the User bounded context. Update `config/services.yaml` wiring.

**Why:** Same naming inconsistency that was fixed in Account BC. The `EventSourced` prefix leaks implementation details into the domain layer.

**Context:** Follow the same pattern used in the Account BC rename (no migration needed — event_type stores event FQCNs, not aggregate/repository names).

**Depends on:** None
