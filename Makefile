.PHONY: build install shell validate lint lint-fix test test-coverage docs-verify check ci audit

DOCKER_COMPOSE ?= docker compose
PHP = $(DOCKER_COMPOSE) run --rm php

build:
	$(DOCKER_COMPOSE) build

install: build
	$(PHP) composer install

shell:
	$(DOCKER_COMPOSE) run --rm php bash

validate:
	$(PHP) composer validate --strict

lint:
	$(PHP) composer lint:all

lint-fix:
	$(PHP) composer lint:fix

test:
	$(PHP) composer test

test-coverage:
	$(PHP) composer test:coverage

docs-verify:
	$(PHP) composer docs:verify

audit:
	$(PHP) composer audit --locked

check:
	$(PHP) composer check

ci:
	$(PHP) composer ci
