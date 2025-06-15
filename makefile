# ITSM Platform Makefile
# Automação de tarefas comuns de desenvolvimento

.PHONY: help
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.DEFAULT_GOAL := help

# Variables
DOCKER_COMPOSE = docker-compose
BACKEND_CONTAINER = itsm-backend
FRONTEND_CONTAINER = itsm-frontend
AI_SERVICE_CONTAINER = itsm-ai-service
DB_CONTAINER = itsm-postgres

# Docker commands
.PHONY: build
build: ## Build all Docker containers
	$(DOCKER_COMPOSE) build --no-cache

.PHONY: build-backend
build-backend: ## Build backend container
	$(DOCKER_COMPOSE) build --no-cache backend

.PHONY: build-frontend
build-frontend: ## Build frontend container
	$(DOCKER_COMPOSE) build --no-cache frontend

.PHONY: build-ai
build-ai: ## Build AI service container
	$(DOCKER_COMPOSE) build --no-cache ai-service

.PHONY: up
up: ## Start all services
	$(DOCKER_COMPOSE) up -d

.PHONY: down
down: ## Stop all services
	$(DOCKER_COMPOSE) down

.PHONY: restart
restart: down up ## Restart all services

.PHONY: logs
logs: ## Show logs for all services
	$(DOCKER_COMPOSE) logs -f

.PHONY: logs-backend
logs-backend: ## Show backend logs
	$(DOCKER_COMPOSE) logs -f backend

.PHONY: logs-frontend
logs-frontend: ## Show frontend logs
	$(DOCKER_COMPOSE) logs -f frontend

.PHONY: logs-ai
logs-ai: ## Show AI service logs
	$(DOCKER_COMPOSE) logs -f ai-service

# Database commands
.PHONY: db-migrate
db-migrate: ## Run database migrations
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan migrate

.PHONY: db-seed
db-seed: ## Seed the database
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan db:seed

.PHONY: db-fresh
db-fresh: ## Fresh migration with seeds
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan migrate:fresh --seed

.PHONY: db-reset
db-reset: ## Reset database
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan migrate:reset

# Shell access
.PHONY: shell-backend
shell-backend: ## Access backend shell
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) bash

.PHONY: shell-frontend
shell-frontend: ## Access frontend shell
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) sh

.PHONY: shell-ai
shell-ai: ## Access AI service shell
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) bash

.PHONY: shell-db
shell-db: ## Access database shell
	$(DOCKER_COMPOSE) exec $(DB_CONTAINER) psql -U itsm_user -d itsm_platform

# Laravel specific
.PHONY: artisan
artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan $(cmd)

.PHONY: tinker
tinker: ## Start Laravel tinker
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan tinker

.PHONY: composer
composer: ## Run composer command (usage: make composer cmd="require package")
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) composer $(cmd)

.PHONY: queue-work
queue-work: ## Start queue worker
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan queue:work

.PHONY: cache-clear
cache-clear: ## Clear all Laravel caches
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan cache:clear
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan config:clear
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan route:clear
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan view:clear

# Frontend specific
.PHONY: npm
npm: ## Run npm command (usage: make npm cmd="install package")
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm $(cmd)

.PHONY: npm-install
npm-install: ## Install npm dependencies
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm install

.PHONY: npm-build
npm-build: ## Build frontend assets
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm run build

.PHONY: npm-dev
npm-dev: ## Run frontend in dev mode
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm run dev

# Python/AI specific
.PHONY: pip
pip: ## Run pip command (usage: make pip cmd="install package")
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) pip $(cmd)

.PHONY: pytest
pytest: ## Run Python tests
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) pytest

.PHONY: alembic
alembic: ## Run alembic command (usage: make alembic cmd="revision -m 'message'")
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) alembic $(cmd)

# Testing
.PHONY: test
test: test-backend test-frontend test-ai ## Run all tests

.PHONY: test-backend
test-backend: ## Run backend tests
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan test

.PHONY: test-frontend
test-frontend: ## Run frontend tests
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm run test:unit

.PHONY: test-ai
test-ai: ## Run AI service tests
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) pytest

.PHONY: test-e2e
test-e2e: ## Run E2E tests
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm run test:e2e

# Code quality
.PHONY: lint
lint: lint-backend lint-frontend lint-ai ## Run all linters

.PHONY: lint-backend
lint-backend: ## Run PHP linter
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) ./vendor/bin/phpcs
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) ./vendor/bin/phpstan analyse

.PHONY: lint-frontend
lint-frontend: ## Run ESLint
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm run lint

.PHONY: lint-ai
lint-ai: ## Run Python linter
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) black --check app/
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) isort --check-only app/
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) mypy app/

.PHONY: format
format: format-backend format-frontend format-ai ## Format all code

.PHONY: format-backend
format-backend: ## Format PHP code
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) ./vendor/bin/php-cs-fixer fix

.PHONY: format-frontend
format-frontend: ## Format JS/TS code
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm run format

.PHONY: format-ai
format-ai: ## Format Python code
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) black app/
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) isort app/

# Setup commands
.PHONY: setup
setup: build up setup-backend setup-frontend setup-ai ## Complete setup

.PHONY: setup-backend
setup-backend: ## Setup backend
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) composer install
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan key:generate
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan migrate --seed
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan storage:link

.PHONY: setup-frontend
setup-frontend: ## Setup frontend
	$(DOCKER_COMPOSE) exec $(FRONTEND_CONTAINER) npm install

.PHONY: setup-ai
setup-ai: ## Setup AI service
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) pip install -r requirements.txt
	$(DOCKER_COMPOSE) exec $(AI_SERVICE_CONTAINER) alembic upgrade head

.PHONY: fresh
fresh: down ## Fresh install with clean volumes
	docker-compose down -v
	docker system prune -f
	make setup

# Utility commands
.PHONY: ps
ps: ## Show running containers
	$(DOCKER_COMPOSE) ps

.PHONY: stats
stats: ## Show container stats
	docker stats

.PHONY: clean
clean: ## Clean up containers and volumes
	$(DOCKER_COMPOSE) down -v
	docker system prune -f

.PHONY: backup
backup: ## Backup database
	$(DOCKER_COMPOSE) exec $(DB_CONTAINER) pg_dump -U itsm_user itsm_platform > backup_$(shell date +%Y%m%d_%H%M%S).sql

.PHONY: restore
restore: ## Restore database (usage: make restore file=backup.sql)
	$(DOCKER_COMPOSE) exec -T $(DB_CONTAINER) psql -U itsm_user itsm_platform < $(file)

# Development helpers
.PHONY: create-admin
create-admin: ## Create admin user
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan make:admin

.PHONY: generate-docs
generate-docs: ## Generate API documentation
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan l5-swagger:generate

.PHONY: ide-helper
ide-helper: ## Generate IDE helper files
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan ide-helper:generate
	$(DOCKER_COMPOSE) exec $(BACKEND_CONTAINER) php artisan ide-helper:models -N

# Git helpers
.PHONY: pre-commit
pre-commit: lint test ## Run pre-commit checks

.PHONY: release
release: ## Create release (usage: make release version=v1.0.0)
	git tag -a $(version) -m "Release $(version)"
	git push origin $(version)
