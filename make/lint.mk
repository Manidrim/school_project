lint-php-cs: ## Fix PHP coding standards
	docker compose exec -T php vendor/bin/php-cs-fixer fix --verbose

lint-phpstan: ## Run PHPStan static analysis
	docker compose exec -T php vendor/bin/phpstan analyse --memory-limit=512M

lint-phpmd: ## Run PHPMD code analysis
	docker compose exec -T php vendor/bin/phpmd src text phpmd.xml

lint-phpcs: ## Run PHP_CodeSniffer analysis
	docker compose exec -T php vendor/bin/phpcs --standard=phpcs.xml

lint-deptrac: ## Run Deptrac architecture analysis
	docker compose exec -T php vendor/bin/deptrac analyse --config-file=deptrac.yaml

lint-eslint: ## Run ESLint on PWA
	docker compose exec -T pwa pnpm lint

lint-eslint-fix: ## Fix ESLint issues on PWA
	docker compose exec -T pwa pnpm lint --fix

lint-hadolint: ## Run Hadolint on Dockerfiles
	find . -name "Dockerfile*" -exec hadolint {} \;

test: ## Run PHPUnit tests
	docker compose exec -T php bin/phpunit

test-coverage: ## Run PHPUnit tests with coverage
	docker compose exec -T php bin/phpunit --coverage-html var/coverage --coverage-clover var/coverage/clover.xml

test-coverage-check: ## Run tests and verify 100% coverage
	docker compose exec -T -e XDEBUG_MODE=coverage php bin/phpunit --coverage-clover var/coverage/clover.xml
	docker compose exec -T php php coverage-check.php

test-frontend: ## Run frontend tests
	docker compose exec -T pwa pnpm test

test-frontend-coverage: ## Run frontend tests with coverage
	docker compose exec -T pwa pnpm test:coverage

test-frontend-coverage-check: ## Run frontend tests and verify 100% coverage
	docker compose exec -T pwa pnpm test:coverage:check

lint-php: lint-php-cs lint-phpstan lint-phpmd lint-phpcs lint-deptrac ## Run all PHP linters

lint-frontend: lint-eslint ## Run all frontend linters

lint-docker: lint-hadolint ## Run all Docker linters

lint: lint-php lint-frontend lint-docker ## Run all linters

fix-php: lint-php-cs ## Fix all auto-fixable PHP issues

fix-frontend: lint-eslint-fix ## Fix all auto-fixable frontend issues

test-integration: ## Run all integration tests (API + Frontend)
	docker compose exec -T php bin/phpunit tests/Api/
	@echo "🔍 Checking for frontend integration tests..."
	@if [ $$(docker compose exec -T pwa find __tests__ -name "*.test.tsx" -exec grep -l "integration\|e2e" {} \; 2>/dev/null | wc -l) -gt 0 ]; then \
		echo "✅ Running frontend integration tests..."; \
		docker compose exec -T pwa pnpm test -- --testPathPattern="integration|e2e"; \
	else \
		echo "ℹ️ No dedicated frontend integration tests found - running all frontend tests as integration validation"; \
		docker compose exec -T pwa pnpm test; \
	fi

test-integration-api: ## Run API integration tests only
	docker compose exec -T php bin/phpunit tests/Api/

test-integration-frontend: ## Run frontend integration tests only  
	@echo "🔍 Checking for frontend integration tests..."
	@if [ $$(docker compose exec -T pwa find __tests__ -name "*.test.tsx" -exec grep -l "integration\|e2e" {} \; 2>/dev/null | wc -l) -gt 0 ]; then \
		echo "✅ Running frontend integration tests..."; \
		docker compose exec -T pwa pnpm test -- --testPathPattern="integration|e2e"; \
	else \
		echo "ℹ️ No dedicated frontend integration tests found - running all frontend tests as integration validation"; \
		docker compose exec -T pwa pnpm test; \
	fi

test-no-skip: ## Verify no tests are skipped in the entire test suite
	@echo "🔍 Checking for skipped tests..."
	@if docker compose exec -T php bin/phpunit --dry-run | grep -i "skipped\|todo"; then \
		echo "❌ Found skipped PHP tests!"; \
		exit 1; \
	else \
		echo "✅ No skipped PHP tests found"; \
	fi
	@if docker compose exec -T pwa pnpm test --passWithNoTests --verbose 2>&1 | grep -i "skip\|todo\|pending"; then \
		echo "❌ Found skipped frontend tests!"; \
		exit 1; \
	else \
		echo "✅ No skipped frontend tests found"; \
	fi
	@echo "✅ All tests are active - no skipped tests detected"

coverage-check: test-coverage-check ## Verify 100% test coverage for all code (frontend temporarily disabled)

fix: fix-php fix-frontend lint-php test-no-skip coverage-check ## ZERO TOLERANCE: Fix ALL issues, run ALL linters, NO deprecated/warnings/errors allowed

fix-with-integration: fix test-integration ## Complete fix including integration tests validation

fix-complete: fix lint-hadolint test-frontend ## Complete validation including environment-specific tools (Docker linting + frontend tests)

check-status: ## Quick status check (essential tools only)
	@echo "🔍 Quick Status Check..."
	@docker compose exec -T php vendor/bin/php-cs-fixer fix --dry-run --verbose | head -5
	@docker compose exec -T pwa pnpm lint | head -5
	@echo "✅ Use 'make fix' for complete validation"

.PHONY: lint-php-cs lint-phpstan lint-phpmd lint-phpcs lint-deptrac lint-eslint lint-eslint-fix lint-hadolint lint-php lint-frontend lint-docker lint fix-php fix-frontend fix test test-coverage test-coverage-check test-frontend test-frontend-coverage test-frontend-coverage-check coverage-check