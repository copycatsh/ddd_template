# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-01

### Modular Monolith Complete
- Phase 6.1: User BC polish (DomainEventsTrait, PasswordHasherInterface port, domain events wired)
- ADR 001: Event Sourcing for Account BC
- ADR 002: ES Projections for read side
- Architecture overview fully updated
- Makefile cleaned up

## [0.2.0] - 2026-04-01

### Added
- DomainEventsTrait in Shared kernel (reusable event collection)
- PasswordHasherInterface domain port + SymfonyPasswordHasher adapter
- User::create() static factory with event recording
- DoctrineUserRepository: transactional event dispatch
- UserCreatedEvent + UserEmailChangedEvent wired via Messenger sync transport

### Changed
- AbstractAggregateRoot refactored to use DomainEventsTrait
- CreateUserHandler: single entity creation, uses PasswordHasherInterface port
- UserCreatedEvent: removed hashedPassword from payload (security)
- User constructor: private (creation only via User::create())
- UserFixtures: updated to use User::create()

## [0.1.0] - 2026-03-31

### Added
- Phase 1: Cleanup + Aggregate Factory (factory methods on aggregates)
- Phase 2.5: ES Migration (Account BC fully event-sourced)
- Phase 2.6: UI structure (hexagonal driving adapters)
- Phase 3: ES Projections (account_projections table, O(1) reads)
- Phase 3.1: Domain Refinements (exception hierarchy per BC)
- Phase 4: Domain Services + Specification Pattern + Policy Pattern
- Shared Kernel: Money/Currency VOs + CurrencyMismatchException
