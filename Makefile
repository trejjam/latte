.PHONY: all install cs ecs ecsFix phpstan

all: ecs phpstan
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
