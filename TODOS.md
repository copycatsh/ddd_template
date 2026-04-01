# ddd_template — Roadmap

## Completed

- ✅ Phase 1: Cleanup + Aggregate Factory (factory methods on aggregates)
- ✅ Phase 2.5: ES Migration (Account BC fully event-sourced)
- ✅ Phase 2.6: UI structure (hexagonal driving adapters)
- ✅ Phase 3: ES Projections (account_projections table, O(1) reads)
- ✅ Phase 3.1: Domain Refinements (exception hierarchy, DomainException per BC)
- ✅ Phase 7: Shared Kernel (Money/Currency VOs + CurrencyMismatchException → Shared kernel)
- ✅ Phase 4: Domain Services + Specification + Policy
- ✅ Phase 5: Integration Events
- ✅ Phase 6: User BC completion (delete ES dead code, HTTP API, console command distribution, smoke tests)
- ✅ Phase 6.1: User BC polish (static factory, domain event dispatch via DomainEventsTrait, PasswordHasher port)

---

## Phase 7+: Microservices prep (deferred)

Implement when splitting BCs into separate services:
- Outbox Pattern (at-least-once delivery across DB boundaries)
- Full Saga Pattern (state machine: PENDING→WITHDRAWING→DEPOSITING→COMPLETING→FAILED,
  compensating transactions, idempotency keys)
- CDC (Change Data Capture)
- Microservices split: account-service, user-service, transaction-service, notification-service