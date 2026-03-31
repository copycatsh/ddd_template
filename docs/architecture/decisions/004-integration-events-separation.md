# ADR 004: Integration Events Separation

## Status
Accepted

## Context
Transaction domain events (TransactionCreated/Completed/FailedEvent) were dispatched
via Symfony Messenger to the Notification bounded context. This created a cross-BC
dependency: Notification BC imported Transaction domain model classes directly.

If Transaction BC refactored its domain events (renamed fields, changed structure,
added ES metadata), Notification BC would break. Domain events are internal to a BC;
they should not be part of the public API between contexts.

## Decision
Introduce Integration Events as the public contract between bounded contexts.

- **Domain Events** (`Transaction/Domain/Event/`) remain internal to Transaction BC.
  They carry domain-specific data (TransactionType enum, AbstractDomainEvent base class).
- **Integration Events** (`Shared/Integration/Event/`) are simple `final readonly` DTOs
  with scalar fields only. They carry only the data consumers need.
- **IntegrationEventMapper** translates domain events to integration events.
  The saga creates domain events, maps them, dispatches integration events.
- Notification BC handlers consume integration events only. Zero imports from
  Transaction domain.

This implements the Published Language strategic DDD pattern.

## Consequences

### Positive
- Notification BC is decoupled from Transaction BC domain model
- Integration events form a stable public contract (Published Language)
- Transaction BC can freely refactor domain events without breaking consumers
- Clear separation: domain events = internal, integration events = public API

### Negative
- One additional translation step (mapper) per event dispatch
- Integration event DTOs mirror some domain event data (acceptable duplication
  at the BC boundary — this is the Published Language, not a DDD violation)

### When NOT to use this pattern
- Within a single BC (e.g., Account domain events consumed by Account projections).
  Same-BC communication should use domain events directly.
- For simple CRUD applications without BC boundaries. The overhead is not justified.
