build: ## Build the Docker images
	docker compose build --no-cache

start: ## Start the project
	docker compose up --wait

stop: ## Stop the project
	docker compose down

restart: ## Restart the project
	docker compose restart

logs: ## Show logs
	docker compose logs -f

install-api: ## Install API dependencies
	docker compose exec -T php composer install

install-pwa: ## Install PWA dependencies
	docker compose exec -T pwa pnpm install

install: install-api install-pwa ## Install all dependencies

.PHONY: build start stop restart logs install-api install-pwa install