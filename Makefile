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

.PHONY: info
info: ## Show platform and configuration info
	@echo -e "$(BLUE)Platform Information:$(NC)"
	@echo -e "  OS: $(UNAME_S)"
	@echo -e "  Architecture: $(UNAME_M)"
	@echo -e "  Platform: $(PLATFORM_MSG)"
	@echo -e "  Compose files: $(COMPOSE_FILES)"
	@echo -e ""
	@echo -e "$(BLUE)Docker Version:$(NC)"
	@docker --version
	@docker compose version

##@ Setup

.PHONY: setup
setup: ## Initial project setup
	@echo -e "$(BLUE)Setting up project...$(NC)"
	@if [ ! -f .env ]; then \
		echo -e "$(YELLOW).env not found, creating from .env.dist...$(NC)"; \
		make init-env; \
	fi
	@make up
	@make composer-install
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

##@ PHP/Symfony

.PHONY: bash
bash: ## Enter PHP container bash
	@$(DOCKER_PHP) bash

.PHONY: jwt-generate
jwt-generate: ## Generate JWT keys
	@echo -e "$(BLUE)Generating JWT keys...$(NC)"
	@$(DOCKER_PHP) mkdir -p config/jwt
	@$(DOCKER_PHP) openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:your_passphrase_here
	@$(DOCKER_PHP) openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_passphrase_here
	@$(DOCKER_PHP) chmod 644 config/jwt/private.pem config/jwt/public.pem
	@echo -e "$(GREEN)JWT keys generated!$(NC)"

.PHONY: composer-install
composer-install: ## Install composer dependencies
	@echo -e "$(BLUE)Installing composer dependencies...$(NC)"
	@$(DOCKER_PHP) composer install
	@echo -e "$(GREEN)Composer install complete!$(NC)"

.PHONY: composer-update
composer-update: ## Update composer dependencies
	@$(DOCKER_PHP) composer update

.PHONY: composer-require
composer-require: ## Install composer package (use: make composer-require PKG=vendor/package)
	@$(DOCKER_PHP) composer require $(PKG)

.PHONY: sf
sf: ## Run Symfony console command (use: make sf CMD="list")
	@$(DOCKER_PHP) bin/console $(CMD)

.PHONY: cache-clear
cache-clear: ## Clear Symfony cache
	@$(DOCKER_PHP) bin/console cache:clear
	@echo -e "$(GREEN)Cache cleared!$(NC)"

##@ Database

.PHONY: migrate
migrate: ## Run database migrations
	@echo -e "$(BLUE)Running migrations...$(NC)"
	@$(DOCKER_PHP) bin/console doctrine:migrations:migrate -n
	@echo -e "$(GREEN)Migrations complete!$(NC)"

.PHONY: migration-create
migration-create: ## Create new migration
	@$(DOCKER_PHP) bin/console doctrine:migrations:generate

.PHONY: db-create
db-create: ## Create database
	@$(DOCKER_PHP) bin/console doctrine:database:create

.PHONY: db-drop
db-drop: ## Drop database (WARNING!)
	@$(DOCKER_PHP) bin/console doctrine:database:drop --force

.PHONY: db-reset
db-reset: db-drop db-create migrate ## Reset database

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
	@echo -e "$(BLUE)Running tests...$(NC)"
	@$(DOCKER_PHP) vendor/bin/phpunit
	@echo -e "$(GREEN)Tests complete!$(NC)"

.PHONY: test-unit
test-unit: ## Run unit tests
	@$(DOCKER_PHP) vendor/bin/phpunit --testsuite=Unit

.PHONY: test-integration
test-integration: ## Run integration tests
	@$(DOCKER_PHP) vendor/bin/phpunit --testsuite=Integration

.PHONY: test-coverage
test-coverage: ## Run tests with coverage
	@$(DOCKER_PHP) vendor/bin/phpunit --coverage-html var/coverage

##@ CLI Shortcuts

.PHONY: user-create
user-create: ## Create user (use: make user-create EMAIL=test@example.com PASS=password)
	@$(DOCKER_PHP) bin/console app:create-user $(EMAIL) $(PASS)

.PHONY: account-balance
account-balance: ## Get account balance (use: make account-balance ID=account-id)
	@$(DOCKER_PHP) bin/console app:get-account-balance $(ID)

.PHONY: deposit
deposit: ## Deposit money (use: make deposit ID=account-id AMOUNT=100.00 CURRENCY=USD)
	@$(DOCKER_PHP) bin/console app:deposit-money $(ID) $(AMOUNT) $(CURRENCY)

.PHONY: withdraw
withdraw: ## Withdraw money (use: make withdraw ID=account-id AMOUNT=50.00 CURRENCY=USD)
	@$(DOCKER_PHP) bin/console app:withdraw-money $(ID) $(AMOUNT) $(CURRENCY)

##@ Code Quality

.PHONY: cs-check
cs-check: ## Check coding standards
	@$(DOCKER_PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

.PHONY: cs-fix
cs-fix: ## Fix coding standards
	@$(DOCKER_PHP) vendor/bin/php-cs-fixer fix

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	@$(DOCKER_PHP) vendor/bin/phpstan analyse

##@ Monitoring

.PHONY: health
health: ## Check services health
	@echo -e "$(BLUE)Services Health Status:$(NC)"
	@$(DOCKER_COMPOSE) ps

.PHONY: top
top: ## Show running processes in containers
	@$(DOCKER_COMPOSE) top

.PHONY: stats
stats: ## Show container resource usage
	@docker stats --no-stream $$(docker compose ps -q)

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

.PHONY: info-platform
info-platform: ## Show platform-specific information
	@echo -e "$(BLUE)Platform: $(PLATFORM_MSG)$(NC)"
	@echo -e ""
	@echo -e "Compose files:"
	@echo -e "  $(COMPOSE_FILES)"
	@echo -e ""
	@echo -e "Recommendations:"
ifeq ($(UNAME_S),Darwin)
	@echo -e "  - Use 'make setup-macos' for initial setup"
	@echo -e "  - Vendor directory is in named volume for performance"
	@echo -e "  - Using delegated mount option for better I/O"
else
	@echo -e "  - Use 'make setup' for initial setup"
	@echo -e "  - Standard volume mounts for best performance"
endif

##@ Production

.PHONY: prod-up
prod-up: ## Start in production mode
	@echo -e "$(YELLOW)Starting in PRODUCTION mode...$(NC)"
	@docker compose -f compose.yaml -f compose.prod.yaml up -d
	@echo -e "$(GREEN)Production containers started!$(NC)"

.PHONY: prod-down
prod-down: ## Stop production containers
	@docker compose -f compose.yaml -f compose.prod.yaml down
