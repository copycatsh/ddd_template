# TODOS

## Phase 3: Entity Rename

### Rename EventSourcedAccount → Account

**What:** Rename `EventSourcedAccount` to `Account`, `EventSourcedAccountRepositoryInterface` to `AccountRepositoryInterface`, and all related references.

**Why:** After ES migration (Phase 2.5), the `EventSourced` prefix is noise — there's only one implementation. Clean naming improves readability.

**Cons:** Event store table stores `event_type` as FQCN (e.g., `App\Account\Domain\Entity\EventSourcedAccount`). Renaming without a data migration breaks event deserialization for all existing events.

**Context:** Requires a Doctrine migration + PHP script to update `event_type` column values in `event_store` table. Separate focused PR.

**Depends on:** Phase 2.5 (ES migration) — completed

## Phase 4a: ES Projections (fast-tracked)

### Rebuild AccountReadModelQuery as ES Projection

**What:** Create a projection handler that listens to `AccountCreatedEvent` / `MoneyDepositedEvent` / `MoneyWithdrawnEvent` and builds an `account_read_model` table. Rebuild `AccountReadModelQuery` to read from this projection table.

**Why:** ES query handlers currently reconstitute from event store on every read. `findByUserId()` and `findByUserIdAndCurrency()` scan ALL events in the store (`getAllEvents()`). This degrades linearly with total event count.

**Pros:** O(1) reads instead of O(n) event replay. Proper CQRS+ES read-side pattern. Eliminates full table scans on API endpoints.

**Cons:** Adds eventual consistency (projection may lag behind event store). More infrastructure.

**Context:** This is the standard CQRS+ES read-side pattern. The current scan-all-events approach was acceptable for a learning template but projections are the production-grade solution. Immediate next PR after ES migration.

**Depends on:** Phase 2.5 (ES migration) — completed

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

**Depends on:** UI layer restructuring (this PR)

## Phase 2: Domain Refinements

### Change Money::__construct() to throw DomainException

**What:** `Money::__construct()` throws `\InvalidArgumentException('Amount cannot be negative')` instead of a `DomainException` subclass. This exception bypasses `DomainExceptionSubscriber` and produces a 500 instead of 400.

**Why:** Phase 1 removed handler-level validation, making `Money` the first line of defense for negative amounts. Its exception must be in the `DomainException` hierarchy to map to HTTP 400 via the subscriber.

**Context:** Either have `Money` throw `InvalidAmountException::mustBePositive()` (adds Account domain dependency to a shared VO) or create a shared `InvalidAmountException` in the Shared kernel. The latter is cleaner if Money is used across bounded contexts.

**Depends on:** None

### Add $userId empty-string guard

**What:** `EventSourcedAccount::create()` does not validate that `$userId` is non-empty. Empty strings silently create invalid accounts.

**Why:** In a financial domain, creating an account with an empty user ID is a latent data integrity bug. The error only surfaces later when looking up accounts by user.

**Context:** Consider introducing a `UserId` value object if the pattern recurs across contexts.

**Depends on:** None
