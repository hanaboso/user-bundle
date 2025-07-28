.PHONY: init-dev test

DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer
DM=docker-compose exec -T mongo

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env; \

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install
	$(DE) composer update --dry-run roave/security-advisories

composer-update:
	$(DE) composer update
	$(DE) composer normalize
	$(DE) composer update --dry-run roave/security-advisories

composer-outdated:
	$(DE) composer outdated

# Console
clear-cache:
	$(DE) rm -rf var
	$(DE) php tests/testApp/bin/console cache:warmup --env=test

database-create:
	$(DE) php tests/testApp/bin/console doctrine:database:drop --env=test --force || true
	$(DE) php tests/testApp/bin/console doctrine:database:create --env=test
	$(DE) php tests/testApp/bin/console doctrine:schema:create --env=test

# App dev
init-dev: docker-up-force composer-install

phpcodesniffer:
	$(DE) ./vendor/bin/phpcs --parallel=$$(nproc) --standard=./ruleset.xml src tests

phpcodesnifferfix:
	$(DE) ./vendor/bin/phpcbf --parallel=$$(nproc) --standard=./ruleset.xml src tests

phpstan:
	$(DE) ./vendor/bin/phpstan analyse -c ./phpstan.neon -l 8 src tests

phpunit:
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --runner=WrapperRunner tests/Unit

phpintegration: database-create
	$(DE) sed -i 's/TRUE/FALSE/g' src/Command/PasswordCommandAbstract.php
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --runner=WrapperRunner tests/Integration
	$(DE) sed -i 's/FALSE/TRUE/g' src/Command/PasswordCommandAbstract.php

phpcontroller:
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --runner=WrapperRunner tests/Controller

phpcoverage:
	$(DE) sed -i 's/TRUE/FALSE/g' src/Command/PasswordCommandAbstract.php
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist --coverage-html var/coverage --cache-directory var/cache/coverage --coverage-filter src tests
	$(DE) sed -i 's/FALSE/TRUE/g' src/Command/PasswordCommandAbstract.php

phpcoverage-ci:
	$(DE) sed -i 's/TRUE/FALSE/g' src/Command/PasswordCommandAbstract.php
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh -c 95 -p 1
	$(DE) sed -i 's/FALSE/TRUE/g' src/Command/PasswordCommandAbstract.php

test: docker-up-force composer-install fasttest

fasttest: clear-cache phpcodesniffer phpstan phpunit phpintegration phpcontroller phpcoverage-ci
