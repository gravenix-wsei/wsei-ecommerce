.PHONY: help up build restart logs shell php-exec down init env start stop php-test-init php-run-tests

# Load environment variables from .env file if it exists
ifneq (,$(wildcard .env))
    include .env
    export
endif

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

.env: ## Create .env file from .env.example if it doesn't exist
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ".env file created from .env.example"; \
	else \
		echo ".env file already exists, skipping..."; \
	fi

init: .env build up ## Initialize environment: create .env, build and start containers
	docker compose build
	docker compose up -d

up: ## Create and start containers
	docker compose up -d

start: ## Start containers
	docker compose start

build: ## Build or rebuild services
	docker compose build

restart: ## Restart containers
	docker compose restart

logs: ## Preview container logs (follow mode)
	docker compose logs -f

shell: ## Open shell in PHP container
	docker compose exec -u www-data php bash

php-exec: ## Execute PHP command in container (usage: make php-exec CMD="bin/console cache:clear")
	@if [ -z "$(CMD)" ]; then \
		echo "Error: CMD parameter is required"; \
		echo "Usage: make php-exec CMD=\"bin/console cache:clear\""; \
		exit 1; \
	fi
	docker compose exec -u www-data php $(CMD)

php-test-init: ## Create test database and run migrations (use CLEAR_EXISTING=true to drop existing database)
ifeq ($(CLEAR_EXISTING),true)
	@echo "Dropping existing test database..."
	@docker compose exec -e MYSQL_PWD=${MYSQL_ROOT_PASSWORD} mysql mysql -uroot -e "DROP DATABASE IF EXISTS ecommerce_test;" 2>/dev/null || true
endif
	@echo "Creating test database 'ecommerce_test'..."
	@docker compose exec -e MYSQL_PWD=${MYSQL_ROOT_PASSWORD} mysql mysql -uroot -e "CREATE DATABASE IF NOT EXISTS ecommerce_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	@docker compose exec -e MYSQL_PWD=${MYSQL_ROOT_PASSWORD} mysql mysql -uroot -e "GRANT ALL PRIVILEGES ON ecommerce_test.* TO '${MYSQL_USER}'@'%';"
	@docker compose exec -e MYSQL_PWD=${MYSQL_ROOT_PASSWORD} mysql mysql -uroot -e "FLUSH PRIVILEGES;"
	@echo "Test database 'ecommerce_test' created successfully!"
	@echo "Running migrations in test environment..."
	@docker compose exec -u www-data -e APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction
	@echo "Test database initialized and migrations completed!"

php-run-tests: ## Run PHP unit tests with coverage report
	docker compose exec -u www-data -e XDEBUG_MODE=coverage php bin/phpunit $(FLAGS)

down: ## Stop and remove containers
	docker compose down

stop: ## Stop containers
	docker compose stop
