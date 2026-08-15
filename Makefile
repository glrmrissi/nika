.PHONY: setup dev db-migrate db-seed db-reset lint lint-php lint-twig lint-container lint-yaml check test help githooks

setup:
	cp -n .env.example .env 2>/dev/null || true
	composer install
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console app:kanji:seed
	php bin/console app:admin:create
	php bin/console cache:clear

dev:
	php -S localhost:8000 -t public

db-migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

db-seed:
	php bin/console app:kanji:seed
	php bin/console app:grammar:seed

db-reset:
	php bin/console doctrine:database:drop --force
	php bin/console doctrine:database:create
	$(MAKE) db-migrate
	$(MAKE) db-seed

lint: lint-php lint-twig lint-container lint-yaml

lint-php:
	php scripts/lint.php

lint-twig:
	php bin/console lint:twig templates

lint-container:
	php bin/console lint:container

lint-yaml:
	php bin/console lint:yaml config

check:
	composer validate --strict
	php scripts/check.php

test: check

githooks:
	git config core.hooksPath .githooks

help:
	@echo "Available targets:"
	@echo "  make setup      - first-time project setup"
	@echo "  make dev        - start PHP dev server on localhost:8000"
	@echo "  make db-migrate - run Doctrine migrations"
	@echo "  make db-seed    - seed kanji and grammar data"
	@echo "  make db-reset   - drop, recreate, migrate and seed database"
	@echo "  make lint       - run all linters"
	@echo "  make check      - run full quality gate"
	@echo "  make test       - alias for make check"
	@echo "  make githooks   - configure git hooks path"
