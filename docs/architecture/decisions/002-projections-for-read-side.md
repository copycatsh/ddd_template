# ADR 002: Projections for Account Read Side

## Status
Accepted

## Context
With Event Sourcing (ADR 001), reading an account's current state requires replaying
all events from genesis. This is O(n) per aggregate, where n = number of events.

Read queries — get balance, list user accounts, check account existence for duplicate
prevention — are frequent and latency-sensitive. API endpoints and the `CreateAccountHandler`
uniqueness check both need fast reads. Replaying events on every read is wasteful when
the current state is what callers need 99% of the time.

## Decision
Introduce an `account_projections` table as a synchronously-updated read model.

- **Write path unchanged** — `AccountRepository::save()` persists events to `event_store`
  and dispatches them via Messenger (sync transport).
- **Projection handler** — `AccountProjectionHandler` is a Messenger handler that listens
  for `AccountCreated`, `MoneyDeposited`, `MoneyWithdrawn` events and upserts the
  `account_projections` table. Runs synchronously in the same transaction.
- **Read queries** — `GetAccountBalanceHandler` and `GetUserAccountsHandler` read from
  `account_projections` via `AccountProjectionQuery` port, not from the event store.
- **Uniqueness check** — `CreateAccountHandler` uses `findByUserIdAndCurrency()` on the
  projection table. UNIQUE index on `(user_id, currency)` enforces at the DB level.
- **Rebuild command** — `app:rebuild-account-projections` truncates and replays all events
  to rebuild projections from scratch. Used after schema changes or data corrections.

```
event_store → Messenger (sync) → AccountProjectionHandler → account_projections
                                                                    ↑
read queries ──────────────────── AccountProjectionQuery ───────────┘
```

## Consequences

### Positive
- O(1) reads for balance and account listing (indexed table lookup)
- Projections are atomic with event store writes (sync Messenger, same transaction)
- Projection table schema is optimized for read queries (denormalized, indexed)
- Rebuild command provides recovery path if projections drift or schema changes

### Negative
- Eventual consistency during projection rebuild (truncate + replay window)
- Projection handler must be updated when new event types are added
- Two representations of the same data (event store + projection) — projection
  is derived, event store is authoritative
- Sync projection adds latency to write path (acceptable for current throughput)

### When NOT to use this pattern
- When temporal queries are needed (what was the balance last Tuesday?). Use event
  store replay directly for point-in-time queries.
- When write throughput is the bottleneck. Async projections with eventual consistency
  would decouple write and read latency, at the cost of stale reads.
