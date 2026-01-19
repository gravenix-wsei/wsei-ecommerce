.PHONY: help up build restart logs shell php-exec down init env start stop php-test-init php-run-tests phpmetrics infection e2e-install e2e-test e2e-test-ui

# Load environment variables from .env file if it exists
ifneq (,$(wildcard .env))
    include .env
    export
endif

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "}; /^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

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

down: ## Stop and remove containers
	docker compose down

stop: ## Stop containers
	docker compose stop

logs: ## Preview container logs (follow mode)
	docker compose logs -f

shell: ## Open shell in PHP container
	docker compose exec -u www-data -e XDEBUG_MODE=off php bash

php-exec: ## Execute PHP command in container (usage: make php-exec CMD="bin/console cache:clear")
	@if [ -z "$(CMD)" ]; then \
		echo "Error: CMD parameter is required"; \
		echo "Usage: make php-exec CMD=\"bin/console cache:clear\""; \
		exit 1; \
	fi
	docker compose exec -u www-data -e XDEBUG_MODE=off php $(CMD)

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

php-run-tests: ## Run PHP tests with coverage report
	@COVERAGE_FLAGS=""; \
	if [ -n "$(CHECK_COVERAGE)" ] && [ "$(CHECK_COVERAGE)" != "false" ]; then \
		COVERAGE_FLAGS="$$COVERAGE_FLAGS --coverage-text"; \
	fi; \
	if [ -n "$(CHECK_BRANCHES)" ] && [ "$(CHECK_BRANCHES)" != "false" ]; then \
		COVERAGE_FLAGS="$$COVERAGE_FLAGS --path-coverage"; \
	fi; \
	docker compose exec -u www-data -e XDEBUG_MODE=coverage php bin/phpunit $(FLAGS) $$COVERAGE_FLAGS

php-run-tests-unit: ## Run PHP unit tests
	$(MAKE) php-run-tests FLAGS="--testsuite UnitTests"

php-run-tests-integration: ## Run PHP integration tests
	$(MAKE) php-run-tests FLAGS="--testsuite IntegrationTests"

php-coverage-check: ## Check coverage for changed files (requires diff-cover: pip install diff-cover)
	@echo "Running tests with coverage..."
	@docker compose exec -u www-data php composer test:coverage $(FLAGS)
	@echo ""
	@if command -v diff-cover >/dev/null 2>&1; then \
		echo "Checking coverage for changed files against master branch..."; \
		cd ecommerce && diff-cover phpunit-coverage/cobertura.xml \
			--compare-branch=origin/master \
			--fail-under=100 \
			--format html:phpunit-coverage/diff-coverage.html && \
		echo "✅ All changed lines have 100% coverage!" || \
		{ echo "❌ Changed lines need more test coverage! Check ecommerce/phpunit-coverage/diff-coverage.html"; exit 1; }; \
	else \
		echo "⚠️  diff-cover not installed. Install it with: pip install diff-cover"; \
		echo "Running basic coverage check instead..."; \
		docker compose exec -u www-data php sh -c "cat phpunit-coverage/cobertura.xml | grep -o 'line-rate=\"[^\"]*\"' | head -1"; \
	fi

phpmetrics: ## Generate PHP metrics report
	docker compose exec -u www-data -e XDEBUG_MODE=off php composer phpmetrics

infection: ## Generate PHP Infection report
	docker compose exec -u www-data -e XDEBUG_MODE=off php composer test:infection

phpstan: ## Run PHPStan static analysis
	docker compose exec -u www-data -e XDEBUG_MODE=off php composer phpstan

ecs: ## Run ECS analysis
	docker compose exec -u www-data -e XDEBUG_MODE=off php composer ecs

ecs-fix: ## Fix code style issues with ECS
	docker compose exec -u www-data -e XDEBUG_MODE=off php composer ecs -- --fix

e2e-tests/.env: ## Create e2e test .env file from example
	@if [ ! -f e2e-tests/.env ]; then \
		cp e2e-tests/.env.example e2e-tests/.env; \
		echo "e2e-tests/.env file created from e2e-tests/.env.example"; \
	else \
		echo "e2e-tests/.env file already exists, skipping..."; \
	fi

e2e-tests/node_modules/.e2e-deps-installed: e2e-tests/.env
	npm --prefix e2e-tests install
	npm --prefix e2e-tests run install:browsers
	@touch e2e-tests/node_modules/.e2e-deps-installed

e2e-install: e2e-tests/node_modules/.e2e-deps-installed ## Install e2e test dependencies

e2e-test: e2e-install ## Run e2e tests in CLI
	npm --prefix e2e-tests run test

e2e-test-ui: e2e-install ## Run e2e tests in interactive mode
	npm --prefix e2e-tests run test:ui