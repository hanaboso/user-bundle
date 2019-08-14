.PHONY: .env init-dev test

DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install --ignore-platform-reqs

composer-update:
	$(DE) composer update --ignore-platform-reqs

composer-outdated:
	$(DE) composer outdated

# Console
clear-cache:
	$(DE) sudo rm -rf var/cache
	$(DE) php bin/console cache:warmup --env=test

database-create:
	$(DE) php bin/console doctrine:database:drop --force || true
	$(DE) php bin/console doctrine:database:create
	$(DE) php bin/console doctrine:schema:create

# App dev
init-dev: docker-up-force composer-install

codesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) ./vendor/bin/phpstan analyse -c ./phpstan.neon -l 7 src/ tests/

phpunit:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Unit

phpintegration: database-create
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Integration

phpcontroller:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Controller

test: docker-up-force composer-install fasttest

fasttest: clear-cache codesniffer phpstan phpunit phpintegration phpcontroller
