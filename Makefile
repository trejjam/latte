.PHONY: all install cs ecs ecsFix phpstan test latte-lint

all: ecs phpstan latte-lint test
	@echo "All checks passed"

install:
	composer install

cs: ecs

ecs:
	XDEBUG_CONFIG="remote_enable=0" vendor/bin/ecs check --config=ecs.php src ${ECS_PARAM}

ecsFix:
	$(MAKE) ECS_PARAM="--fix" ecs

phpstan:
	XDEBUG_CONFIG="remote_enable=0" vendor/bin/phpstan analyse -c phpstan.neon

latte-lint:
	XDEBUG_CONFIG="remote_enable=0" php tests/latte-lint-runner.php

test:
	XDEBUG_CONFIG="remote_enable=0" vendor/bin/tester -C tests
