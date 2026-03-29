# TODOS

## Phase 2.5: ES Migration Cleanup

### Align EventSourced handler exceptions to use AccountNotFoundException

**What:** `EventSourcedDepositMoneyHandler` and `EventSourcedWithdrawMoneyHandler` throw generic `\DomainException('Account not found')` instead of `AccountNotFoundException::withId()`.

**Why:** Phase 1 aligns the basic (CRUD) handlers to use `AccountNotFoundException`. The ES variants should follow the same pattern for consistent error handling and HTTP status mapping via `DomainExceptionSubscriber`.

**Context:** Will be resolved naturally when CRUD variants are removed and ES handlers become the primary implementation during ES migration (Phase 2.5). Low priority until then.

**Depends on:** Phase 2.5 (full ES migration)

## Phase 2: Domain Refinements

### Introduce UuidGeneratorInterface port

**What:** `AccountFactory` imports `Symfony\Component\Uid\Uuid` directly in the Domain layer. Replace with a `UuidGeneratorInterface` port in Domain and an infrastructure adapter.

**Why:** Hexagonal architecture requires the Domain layer to be framework-agnostic. Before Phase 1, Account Domain had zero Symfony imports. The factory introduced one.

**Context:** Alternatively, move `AccountFactory` to `Application/Factory/`. Either approach removes the Symfony dependency from the Domain layer.

**Depends on:** None

### Change Money::__construct() to throw DomainException

**What:** `Money::__construct()` throws `\InvalidArgumentException('Amount cannot be negative')` instead of a `DomainException` subclass. This exception bypasses `DomainExceptionSubscriber` and produces a 500 instead of 400.

**Why:** Phase 1 removed handler-level validation, making `Money` the first line of defense for negative amounts. Its exception must be in the `DomainException` hierarchy to map to HTTP 400 via the subscriber.

**Context:** Either have `Money` throw `InvalidAmountException::mustBePositive()` (adds Account domain dependency to a shared VO) or create a shared `InvalidAmountException` in the Shared kernel. The latter is cleaner if Money is used across bounded contexts.

**Depends on:** None

### Add $userId empty-string guard

**What:** Neither `AccountFactory::create()` nor `Account::__construct()` validates that `$userId` is non-empty. Empty strings silently create invalid accounts.

**Why:** In a financial domain, creating an account with an empty user ID is a latent data integrity bug. The error only surfaces later when looking up accounts by user.

**Context:** Best approach is adding a guard in `AccountFactory::create()`. Consider introducing a `UserId` value object if the pattern recurs across contexts.

**Depends on:** None
