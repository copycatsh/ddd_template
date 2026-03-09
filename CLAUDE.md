# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands run inside Docker via Make. The PHP container is required for all PHP/Symfony operations.

```bash
make setup          # First-time setup (copies .env, starts containers, installs deps, runs migrations)
make up / make down # Start/stop containers
make bash           # Shell into the PHP container

# Testing
make test                # All tests
make test-unit           # Unit tests only (tests/Unit/)
make test-integration    # Integration tests only (tests/Integration/)
make test-coverage       # HTML coverage report in var/coverage/

# Run a single test file or method
docker compose exec php vendor/bin/phpunit tests/Unit/Account/Domain/Entity/AccountTest.php
docker compose exec php vendor/bin/phpunit --filter testMethodName

# Code quality
make cs-fix         # Auto-fix coding standards (php-cs-fixer)
make phpstan        # Static analysis

# Symfony console
make sf CMD="list"           # Any bin/console command
make cache-clear

# Database
make migrate        # Run pending migrations
make db-reset       # Drop + create + migrate
make fixtures       # Load demo data (admin@fintech.com/admin123, user@fintech.com/user123)
```

## Architecture

DDD + CQRS + Event Sourcing + Hexagonal Architecture on Symfony 7 / PHP 8.3.

Full reference: [`docs/architecture/overview.md`](docs/architecture/overview.md)

### Key Domain Rules

- Money arithmetic uses `bcmath` (string-based, 2 decimal precision)
- One account per user per currency
- No negative balances; no negative Money amounts
- Currency must match for all operations on an account

## Agents

Custom agents live in `.claude/agents/`. Use them via the Agent tool with the matching `subagent_type`.

| Agent | Model | Purpose |
|-------|-------|---------|
| `backend-project-manager` | inherit | Orchestrates multi-step tasks by delegating to `symfony-ddd-developer` and `php-test-writer`. Never writes code itself. Use for tasks spanning implementation + tests or multiple bounded contexts. |
| `symfony-ddd-developer` | sonnet | Implements DDD components: entities, value objects, events, repositories, handlers, sagas, infrastructure adapters, API Platform processors/providers. |
| `php-test-writer` | haiku | Writes unit and integration tests. Use for TDD (tests before implementation) or adding coverage to existing code. |
| `ddd-mentor` | haiku | Read-only teacher agent. Explains DDD/CQRS/Event Sourcing patterns using concrete examples from `src/`. Use for "how does X work" and "show me an example of Y" questions. |

## Task Management

- Plans and task specs: `docs/plans/` and `docs/tasks/`
- Use `/lt` to list tasks, `/ct` to create, `/dt` to complete, `/cl` to clear completed
