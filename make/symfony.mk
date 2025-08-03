console: ## Symfony console
	docker compose exec -T php bin/console

cache-clear: ## Clear Symfony cache
	docker compose exec -T php bin/console cache:clear

migrations: ## Run database migrations
	docker compose exec -T php bin/console doctrine:migrations:migrate --no-interaction

database-create: ## Create test database
	docker compose exec -T php bin/console -e test doctrine:database:create

schema-validate: ## Validate Doctrine schema
	docker compose exec -T php bin/console -e test doctrine:schema:validate

.PHONY: console cache-clear migrations database-create schema-validate