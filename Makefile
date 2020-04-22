.PHONY: init-dev test

DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer
DM=docker-compose exec -T mongo

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g" \
		-e "s/{DEV_GID}/$(shell id -u)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo "\/tmp\/.ssh-auth-sock"; else echo '\/tmp\/.nope'; fi)/g" \
		.env.dist >> .env; \

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install --no-suggest
	$(DE) composer update --dry-run roave/security-advisories

composer-update:
	$(DE) composer update --no-suggest
	$(DE) composer update --dry-run roave/security-advisories

composer-outdated:
	$(DE) composer outdated

# Console
clear-cache:
	$(DE) rm -rf var/log
	$(DE) php tests/testApp/bin/console cache:clear --env=test
	$(DE) php tests/testApp/bin/console cache:warmup --env=test

database-create:
	$(DE) php tests/testApp/bin/console doctrine:database:drop --env=test --force || true
	$(DE) php tests/testApp/bin/console doctrine:database:create --env=test
	$(DE) php tests/testApp/bin/console doctrine:schema:create --env=test
	$(DM) /bin/bash -c "mongo <<< 'use user;'" ; \
	for i in 1 2 3 4; do \
		$(DM) /bin/bash -c "mongo <<< 'use user$$i;'" ; \
	done

# App dev
init-dev: docker-up-force composer-install

phpcodesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml src tests

phpstan:
	$(DE) ./vendor/bin/phpstan analyse -c ./phpstan.neon -l 8 src tests

phpunit:
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --runner=WrapperRunner tests/Unit

phpintegration: database-create
	$(DE) sed -i 's/TRUE/FALSE/g' src/Command/PasswordCommandAbstract.php
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --runner=WrapperRunner tests/Integration
	$(DE) sed -i 's/FALSE/TRUE/g' src/Command/PasswordCommandAbstract.php

phpcontroller:
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --runner=WrapperRunner tests/Controller

phpcoverage:
	$(DE) sed -i 's/TRUE/FALSE/g' src/Command/PasswordCommandAbstract.php
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist --coverage-html var/coverage --whitelist src tests
	$(DE) sed -i 's/FALSE/TRUE/g' src/Command/PasswordCommandAbstract.php

phpcoverage-ci:
	$(DE) sed -i 's/TRUE/FALSE/g' src/Command/PasswordCommandAbstract.php
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh -c 99
	$(DE) sed -i 's/FALSE/TRUE/g' src/Command/PasswordCommandAbstract.php

test: docker-up-force composer-install fasttest

fasttest: clear-cache phpcodesniffer phpstan phpunit phpintegration phpcontroller phpcoverage-ci
