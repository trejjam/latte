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
	XDEBUG_CONFIG="remote_enable=0" php -r "require 'vendor/autoload.php'; \$$linter = new Latte\Tools\Linter(debug: false, strict: true); \$$latte = \$$linter->getEngine(); \$$latte->setStrictParsing(); \$$latte->addExtension(new Trejjam\Latte\TrejjamLatteExtension()); exit(\$$linter->scanDirectory('tests/fixtures') ? 0 : 1);"

test:
	XDEBUG_CONFIG="remote_enable=0" vendor/bin/tester -C tests
