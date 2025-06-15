# Defender360 ITSM Platform - Makefile
.PHONY: help

# Default target
.DEFAULT_GOAL := help

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
CYAN   := $(shell tput -Txterm setaf 6)
RESET  := $(shell tput -Txterm sgr0)

## Help
help: ## Show this help message
	@echo ''
	@echo '${CYAN}Defender360 ITSM Platform${RESET}'
	@echo '${WHITE}Available commands:${RESET}'
	@echo ''
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "${YELLOW}%-20s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## Docker Commands
up: ## Start all containers
	docker-compose up -d

down: ## Stop all containers
	docker-compose down

restart: ## Restart all containers
	docker-compose restart

build: ## Build all containers
	docker-compose build

rebuild: ## Rebuild and start all containers
	docker-compose down
	docker-compose build --no-cache
	docker-compose up -d

logs: ## Show logs for all containers
	docker-compose logs -f

logs-backend: ## Show backend logs
	docker-compose logs -f backend

logs-queue: ## Show queue worker logs
	docker-compose logs -f queue

ps: ## Show running containers
	docker-compose ps

## Laravel Commands
artisan: ## Run artisan command (usage: make artisan cmd="migrate")
	docker-compose exec backend php artisan $(cmd)

migrate: ## Run database migrations
	docker-compose exec backend php artisan migrate

migrate-fresh: ## Fresh migration with seeders
	docker-compose exec backend php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker-compose exec backend php artisan db:seed

tinker: ## Start Laravel tinker
	docker-compose exec backend php artisan tinker

composer: ## Run composer command (usage: make composer cmd="require package")
	docker-compose exec backend composer $(cmd)

composer-install: ## Install composer dependencies
	docker-compose exec backend composer install

composer-update: ## Update composer dependencies
	docker-compose exec backend composer update

## Testing Commands
test: ## Run all tests
	docker-compose exec backend php artisan test

test-unit: ## Run unit tests
	docker-compose exec backend php artisan test --testsuite=Unit

test-feature: ## Run feature tests
	docker-compose exec backend php artisan test --testsuite=Feature

test-coverage: ## Run tests with coverage
	docker-compose exec backend php artisan test --coverage

## Cache Commands
clear-all: ## Clear all Laravel caches
	docker-compose exec backend php artisan cache:clear
	docker-compose exec backend php artisan config:clear
	docker-compose exec backend php artisan route:clear
	docker-compose exec backend php artisan view:clear

cache-all: ## Cache all configurations
	docker-compose exec backend php artisan config:cache
	docker-compose exec backend php artisan route:cache
	docker-compose exec backend php artisan view:cache

## Permissions
fix-permissions: ## Fix storage and cache permissions
	docker-compose exec backend chown -R www-data:www-data storage bootstrap/cache
	docker-compose exec backend chmod -R 775 storage bootstrap/cache

## Key Generation
key-generate: ## Generate application key
	docker-compose exec backend php artisan key:generate

## Queue Management
queue-restart: ## Restart queue workers
	docker-compose exec backend php artisan queue:restart

queue-work: ## Start queue worker manually
	docker-compose exec backend php artisan queue:work --sleep=3 --tries=3

horizon: ## Start Horizon
	docker-compose exec backend php artisan horizon

## Development Commands
npm-install: ## Install npm dependencies
	docker-compose exec backend npm install

npm-dev: ## Run npm dev
	docker-compose exec backend npm run dev

npm-build: ## Run npm build
	docker-compose exec backend npm run build

## Database Commands
db-backup: ## Backup database
	docker-compose exec postgres pg_dump -U itsm_user itsm_platform > backup/db_backup_$(shell date +%Y%m%d_%H%M%S).sql

db-restore: ## Restore database (usage: make db-restore file=backup.sql)
	docker-compose exec -T postgres psql -U itsm_user itsm_platform < $(file)

## Verification Commands
verify-setup: ## Verify that all services are running correctly
	@echo "${GREEN}🔍 Verifying setup...${RESET}"
	@echo "Checking health endpoint..."
	@curl -s http://localhost:8000/health || echo "❌ Health check failed"
	@echo "\n${GREEN}Container status:${RESET}"
	@docker-compose ps
	@echo "\n${GREEN}Database status:${RESET}"
	@docker-compose exec postgres pg_isready -U itsm_user || echo "❌ Database not ready"
	@echo "\n${GREEN}Redis status:${RESET}"
	@docker-compose exec redis redis-cli ping || echo "❌ Redis not ready"

check-logs: ## Check for errors in logs
	@echo "${GREEN}Checking for errors in logs...${RESET}"
	@docker-compose logs --tail=100 | grep -E "(ERROR|CRITICAL|FATAL)" || echo "✅ No critical errors found"

## Installation
install: ## Full installation process
	@echo "${GREEN}Installing Defender360 ITSM Platform...${RESET}"
	@make build
	@make up
	@sleep 10
	@make composer-install
	@make key-generate
	@make migrate-fresh
	@make fix-permissions
	@make verify-setup
	@echo "${GREEN}✅ Installation complete!${RESET}"

## Maintenance
clean: ## Clean up containers and volumes (WARNING: Deletes all data)
	@echo "${YELLOW}⚠️  WARNING: This will delete all containers and data!${RESET}"
	@echo "Press Ctrl+C to cancel, or wait 5 seconds to continue..."
	@sleep 5
	docker-compose down -v
	rm -rf backend/vendor backend/node_modules
	@echo "${GREEN}✅ Cleanup complete${RESET}"

## Monitoring
monitor: ## Open monitoring dashboards
	@echo "${GREEN}Opening monitoring dashboards...${RESET}"
	@echo "Horizon: http://localhost:8000/horizon"
	@echo "Mailpit: http://localhost:8025"
	@echo "MinIO: http://localhost:9001"
	@echo "Elasticsearch: http://localhost:9200"

## Frontend Operations
frontend-install: ## Install frontend dependencies
	cd frontend && npm install

frontend-dev: ## Start frontend development server
	cd frontend && npm run dev

frontend-build: ## Build frontend for production
	cd frontend && npm run build

frontend-test: ## Run frontend tests
	cd frontend && npm run test:unit

frontend-lint: ## Lint frontend code
	cd frontend && npm run lint

frontend-type-check: ## Check TypeScript types
	cd frontend && npm run type-check

frontend-logs: ## Show frontend logs
	docker-compose logs -f frontend

## Shell Access
shell: ## Access backend container shell
	docker-compose exec backend sh

shell-frontend: ## Access frontend container shell
	docker-compose exec frontend sh

shell-db: ## Access database shell
	docker-compose exec postgres psql -U itsm_user itsm_platform

shell-redis: ## Access Redis CLI
	docker-compose exec redis redis-cli