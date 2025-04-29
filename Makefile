include .env

PHP_VERSION ?= 8.1
DOCKER_COMPOSE ?= docker compose
DC_RUN_PHP = $(DOCKER_COMPOSE) run --rm php

.DEFAULT_GOAL : help

help: ## Show this help
	@printf "\033[33m%s:\033[0m\n" 'Available commands'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z0-9_-]+:.*?## / {printf "  \033[32m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
all: update all-checks ## Update to latest and run all checks
all-lowest: update-lowest all-checks ## Update to lowest dependencies and run all checks
all-checks: rector style deptrac packages-composer phan psalm phpstan test ## Run all checks
pull: ## Pull latest developer image
	$(DOCKER_COMPOSE) pull php
build: ## Build developer image locally
	docker build docker/ --build-arg PHP_VERSION=${PHP_VERSION} -t ghcr.io/open-telemetry/opentelemetry-php/opentelemetry-php-base:${PHP_VERSION}
install: ## Install dependencies
	$(DC_RUN_PHP) env XDEBUG_MODE=off composer install
update: ## Update dependencies
	$(DC_RUN_PHP) env XDEBUG_MODE=off composer update
update-lowest: ## Update dependencies to lowest supported versions
	$(DC_RUN_PHP) env XDEBUG_MODE=off composer update --prefer-lowest
test: ## Run tests
	$(DC_RUN_PHP) env XDEBUG_MODE=coverage vendor/bin/phpunit --colors=always
test-verbose: ## Run tests with verbose (testdox) output
	$(DC_RUN_PHP) env XDEBUG_MODE=coverage vendor/bin/phpunit --colors=always --testdox --coverage-clover coverage.clover --coverage-html=tests/coverage/html --log-junit=junit.xml
test-coverage: ## Run tests and generate code coverage
	$(DC_RUN_PHP) env XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html=tests/coverage/html
phan: ## Run phan
	$(DC_RUN_PHP) env XDEBUG_MODE=off env PHAN_DISABLE_XDEBUG_WARN=1 vendor-bin/phan/vendor/bin/phan
psalm: ## Run psalm
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor-bin/psalm/vendor/bin/psalm --threads=1 --no-cache
psalm-info: ## Run psalm and show info
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor-bin/psalm/vendor/bin/psalm --show-info=true --threads=1
phpstan: ## Run phpstan
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor/bin/phpstan analyse --memory-limit=256M
infection: ## Run infection (mutation testing)
	$(DC_RUN_PHP) env XDEBUG_MODE=coverage php -d memory_limit=1024M vendor-bin/infection/vendor/bin/infection --threads=max
packages-composer: ## Validate composer packages
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor/bin/otel packages:composer:validate
benchmark: ## Run phpbench
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor-bin/phpbench/vendor/bin/phpbench run --report=default
phpmetrics: ## Run php metrics
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor-bin/phpmetrics/vendor/bin/phpmetrics --config=./phpmetrics.json --junit=junit.xml
smoke-test-examples: ## Run smoke test examples
	$(DC_RUN_PHP) php ./examples/basic.php
bash: ## bash shell into container
	$(DC_RUN_PHP) bash
style: ## Run style check/fix
	$(DC_RUN_PHP) env XDEBUG_MODE=off env PHP_CS_FIXER_IGNORE_ENV=1 vendor-bin/php-cs-fixer/vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --using-cache=no -vvv
rector-write: ## Run rector
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor-bin/rector/vendor/bin/rector process
rector: ## Run rector (dry-run)
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor-bin/rector/vendor/bin/rector process --dry-run
deptrac: ## Run deptrac
	$(DC_RUN_PHP) env XDEBUG_MODE=off vendor-bin/deptrac/vendor/bin/deptrac --formatter=table --report-uncovered --no-cache
