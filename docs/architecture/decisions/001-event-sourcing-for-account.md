# ADR 001: Event Sourcing for Account Bounded Context

## Status
Accepted

## Context
The Account aggregate manages financial balances through deposits, withdrawals, and
transfers. For a fintech domain, two properties are critical:

1. **Audit trail** — every balance change must be traceable to a specific operation
   with a timestamp. Regulators and users need to see exactly how a balance reached
   its current value.
2. **Correctness** — the balance is not a stored value but a derived fact: the sum of
   all credits minus all debits. Storing a mutable balance field risks drift between
   the balance and the actual transaction history.

With Event Sourcing, the balance is always `replay(events)`. There is no stored balance
that can diverge from reality. The event stream IS the source of truth.

## Decision
Account BC uses Event Sourcing exclusively. The `Account` aggregate extends
`AbstractAggregateRoot` and persists state as a sequence of domain events
(`AccountCreatedEvent`, `MoneyDepositedEvent`, `MoneyWithdrawnEvent`) in the
`event_store` table via `DoctrineEventStore`.

State is reconstructed by replaying events through `apply{EventName}()` methods.
Optimistic concurrency is enforced via version checks on write.

```
Account::create()     → recordEvent(AccountCreatedEvent)
Account::deposit()    → recordEvent(MoneyDepositedEvent)
Account::withdraw()   → recordEvent(MoneyWithdrawnEvent)

AccountRepository::save()    → EventStore::saveEvents() + Messenger dispatch
AccountRepository::findById() → EventStore::getEventsForAggregate() → Account::reconstitute()
```

CRUD Account entity and handlers were removed in Phase 2.5. All Account operations
go through the event store.

## Consequences

### Positive
- Complete audit trail by construction — every state change is an immutable event
- Balance correctness guaranteed — no mutable field, balance = replay(events)
- Temporal queries possible — account state at any point in time via partial replay
- Domain events available for cross-BC integration (projections, notifications)
- Natural fit for CQRS — events drive both write model and read projections

### Negative
- Read performance is O(n) per aggregate load without projections (see ADR 002)
- Event schema evolution requires migration strategy (upcasting or versioned events)
- More complex than CRUD for a pattern that only one BC currently uses
- Snapshots not implemented — replay from genesis for every load (acceptable at
  current scale, revisit if event counts per aggregate exceed ~1000)

### When NOT to use this pattern
- User BC: CRUD entity with Doctrine ORM. No audit trail requirement, simple
  read/write. Domain events dispatched via DomainEventsTrait, not event sourcing.
- Transaction BC: CRUD entity. Transactions are immutable records, not aggregates
  that evolve through events.
- Notification BC: Log entity. Append-only, no state transitions.
