.PHONY: help up build restart logs shell php-exec down init env start stop

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

down: ## Stop and remove containers
	docker compose down

stop: ## Stop containers
	docker compose stop
