.PHONY: all install cs ecs ecsFix phpstan test

all: ecs phpstan test
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

test:
	XDEBUG_CONFIG="remote_enable=0" vendor/bin/tester -C tests
