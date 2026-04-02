# Fintech DDD Project - Docker Management
# Usage: make <target>

# Force bash shell for proper color output on all platforms
SHELL := /bin/bash

# Platform detection
UNAME_S := $(shell uname -s)
UNAME_M := $(shell uname -m)

# Color output (use with echo -e for proper rendering)
BLUE := \\033[0;34m
GREEN := \\033[0;32m
YELLOW := \\033[0;33m
RED := \\033[0;31m
NC := \\033[0m # No Color

# Docker Compose files based on platform
ifeq ($(UNAME_S),Darwin)
	# macOS
	ifeq ($(UNAME_M),arm64)
		# Apple Silicon
		COMPOSE_FILES := -f compose.yaml -f compose.macos.yaml
		PLATFORM_MSG := Apple Silicon (M1/M2/M3)
	else
		# Intel Mac
		COMPOSE_FILES := -f compose.yaml -f compose.macos.yaml
		PLATFORM_MSG := macOS Intel
	endif
else
	# Linux/WSL
	COMPOSE_FILES := -f compose.yaml
	PLATFORM_MSG := Linux/WSL
endif

DOCKER_COMPOSE := docker compose $(COMPOSE_FILES)
DOCKER_PHP := $(DOCKER_COMPOSE) exec php
DOCKER_MYSQL := $(DOCKER_COMPOSE) exec mysql

.DEFAULT_GOAL := help

##@ General

.PHONY: help
help: ## Display this help message
	@echo -e "$(BLUE)Fintech DDD Project - Docker Management$(NC)"
	@echo -e "$(YELLOW)Platform: $(PLATFORM_MSG)$(NC)"
	@echo -e ""
	@awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make $(GREEN)<target>$(NC)\n"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(BLUE)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Setup

.PHONY: setup
setup: ## Initial project setup
	@echo -e "$(BLUE)Setting up project...$(NC)"
	@if [ ! -f .env ]; then \
		echo -e "$(YELLOW).env not found, creating from .env.dist...$(NC)"; \
		make init-env; \
	fi
	@make up
	@$(DOCKER_PHP) composer install
	@make migrate
	@echo -e "$(GREEN)Setup complete!$(NC)"
	@make info-urls

.PHONY: setup-macos
setup-macos: ## Setup with macOS optimizations
	@echo -e "$(BLUE)Setting up for macOS...$(NC)"
	@if [ ! -f .env ]; then \
		make init-env; \
	fi
	@make setup

.PHONY: init-env
init-env: ## Initialize .env file from .env.dist
	@echo -e "$(BLUE)Creating .env file from .env.dist...$(NC)"
	@if [ -f .env ]; then \
		echo -e "$(YELLOW).env already exists, skipping...$(NC)"; \
	else \
		if [ ! -f .env.dist ]; then \
			echo -e "$(RED)Error: .env.dist file not found!$(NC)"; \
			exit 1; \
		fi; \
		cp .env.dist .env; \
		RANDOM_SECRET=$$(openssl rand -hex 32); \
		sed -i.bak "s/change_me_to_random_secret_key_at_least_32_chars/$$RANDOM_SECRET/" .env; \
		rm -f .env.bak; \
		echo -e "$(GREEN).env file created successfully from .env.dist!$(NC)"; \
		echo -e "$(YELLOW)APP_SECRET has been generated automatically.$(NC)"; \
	fi

##@ Docker Control

.PHONY: up
up: ## Start all containers
	@echo -e "$(BLUE)Starting containers...$(NC)"
	@$(DOCKER_COMPOSE) up -d
	@echo -e "$(GREEN)Containers started!$(NC)"
	@make ps

.PHONY: down
down: ## Stop all containers
	@echo -e "$(YELLOW)Stopping containers...$(NC)"
	@$(DOCKER_COMPOSE) down
	@echo -e "$(GREEN)Containers stopped!$(NC)"

.PHONY: restart
restart: down up ## Restart all containers

.PHONY: rebuild
rebuild: ## Rebuild and restart containers
	@echo -e "$(BLUE)Rebuilding containers...$(NC)"
	@$(DOCKER_COMPOSE) up -d --build
	@echo -e "$(GREEN)Rebuild complete!$(NC)"

.PHONY: ps
ps: ## Show container status
	@$(DOCKER_COMPOSE) ps

.PHONY: logs
logs: ## Show logs (use: make logs SERVICE=php)
	@$(DOCKER_COMPOSE) logs -f $(SERVICE)

.PHONY: clean
clean: ## Remove containers and volumes (WARNING: deletes data!)
	@echo -e "$(RED)This will remove all containers and data. Continue? [y/N]$(NC)" && read ans && [ $${ans:-N} = y ]
	@$(DOCKER_COMPOSE) down -v
	@echo -e "$(GREEN)Cleanup complete!$(NC)"

##@ Database

.PHONY: migrate
migrate: ## Run database migrations
	@echo -e "$(BLUE)Running migrations...$(NC)"
	@$(DOCKER_PHP) bin/console doctrine:migrations:migrate -n
	@echo -e "$(GREEN)Migrations complete!$(NC)"

.PHONY: db-reset
db-reset: ## Drop + create + migrate database (WARNING: deletes data!)
	@$(DOCKER_PHP) bin/console doctrine:database:drop --force
	@$(DOCKER_PHP) bin/console doctrine:database:create
	@make migrate

.PHONY: fixtures
fixtures: ## Load data fixtures (demo users & accounts)
	@echo -e "$(BLUE)Loading fixtures...$(NC)"
	@$(DOCKER_PHP) bin/console doctrine:fixtures:load -n
	@echo -e "$(GREEN)Fixtures loaded!$(NC)"
	@echo -e ""
	@echo -e "$(YELLOW)Demo users:$(NC)"
	@echo -e "  Admin:   admin@fintech.com   / admin123"
	@echo -e "  User:    user@fintech.com    / user123"
	@echo -e "  Another: another@fintech.com / another123"

.PHONY: db-seed
db-seed: migrate fixtures ## Run migrations and load fixtures

.PHONY: mysql
mysql: ## Enter MySQL CLI
	@$(DOCKER_MYSQL) mysql -u fintech_user -pfintech_pass fintech_db

.PHONY: mysql-root
mysql-root: ## Enter MySQL CLI as root
	@$(DOCKER_MYSQL) mysql -u root -proot

.PHONY: db-backup
db-backup: ## Backup database to backup.sql
	@echo -e "$(BLUE)Backing up database...$(NC)"
	@$(DOCKER_MYSQL) mysqldump -u root -proot fintech_db > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo -e "$(GREEN)Backup complete!$(NC)"

.PHONY: db-restore
db-restore: ## Restore database from backup.sql
	@echo -e "$(YELLOW)Restoring database from backup.sql...$(NC)"
	@$(DOCKER_COMPOSE) exec -T mysql mysql -u root -proot fintech_db < backup.sql
	@echo -e "$(GREEN)Restore complete!$(NC)"

##@ Testing

.PHONY: test
test: ## Run all tests
	@$(DOCKER_PHP) vendor/bin/phpunit

.PHONY: test-unit
test-unit: ## Run unit tests
	@$(DOCKER_PHP) vendor/bin/phpunit --testsuite=Unit

.PHONY: test-integration
test-integration: ## Run integration tests
	@$(DOCKER_PHP) vendor/bin/phpunit --testsuite=Integration

.PHONY: test-coverage
test-coverage: ## Run tests with coverage report
	@$(DOCKER_PHP) vendor/bin/phpunit --coverage-html var/coverage

##@ Code Quality

.PHONY: cs-check
cs-check: ## Check coding standards (dry run)
	@$(DOCKER_PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

.PHONY: cs-fix
cs-fix: ## Fix coding standards
	@$(DOCKER_PHP) vendor/bin/php-cs-fixer fix

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	@$(DOCKER_PHP) vendor/bin/phpstan analyse

##@ CLI Shortcuts

.PHONY: routes
routes: ## Show all API routes
	@$(DOCKER_PHP) bin/console debug:router | grep api

.PHONY: console
console: ## Run Symfony console command (use: make console CMD="list")
	@$(DOCKER_PHP) bin/console $(CMD)

.PHONY: account-balance
account-balance: ## Get account balance (use: make account-balance ID=uuid)
	@$(DOCKER_PHP) bin/console app:get-account-balance $(ID)

.PHONY: deposit
deposit: ## Deposit money (use: make deposit ID=uuid AMOUNT=100.00 CURRENCY=USD)
	@$(DOCKER_PHP) bin/console app:deposit-money $(ID) $(AMOUNT) $(CURRENCY)

.PHONY: withdraw
withdraw: ## Withdraw money (use: make withdraw ID=uuid AMOUNT=50.00 CURRENCY=USD)
	@$(DOCKER_PHP) bin/console app:withdraw-money $(ID) $(AMOUNT) $(CURRENCY)

##@ Information

.PHONY: info-urls
info-urls: ## Show all service URLs
	@echo -e "$(BLUE)Service URLs:$(NC)"
	@echo -e "  $(GREEN)API:$(NC)          http://localhost:8028"
	@echo -e "  $(GREEN)API Docs:$(NC)     http://localhost:8028/api"
	@echo -e "  $(GREEN)Adminer:$(NC)      http://localhost:8080"
	@echo -e "  $(GREEN)Mailpit:$(NC)      http://localhost:8025"
	@echo -e ""
	@echo -e "$(BLUE)Database Connection:$(NC)"
	@echo -e "  Host:     localhost"
	@echo -e "  Port:     3327"
	@echo -e "  Database: fintech_db"
	@echo -e "  User:     fintech_user"
	@echo -e "  Password: fintech_pass"

##@ Production

.PHONY: prod-up
prod-up: ## Start in production mode
	@echo -e "$(YELLOW)Starting in PRODUCTION mode...$(NC)"
	@docker compose -f compose.yaml -f compose.prod.yaml up -d
	@echo -e "$(GREEN)Production containers started!$(NC)"

.PHONY: prod-down
prod-down: ## Stop production containers
	@docker compose -f compose.yaml -f compose.prod.yaml down
