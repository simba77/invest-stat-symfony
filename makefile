-include .env .env.local

USER_ID ?= $(shell id -u)
PHP_CONTAINER = $(shell docker ps -q -f name=${COMPOSE_PROJECT_NAME}.php-fpm)
DB_CONTAINER = $(shell docker ps -q -f name=${COMPOSE_PROJECT_NAME}.mariadb)

restart: stop up

build:
	@echo "Building containers"
	@USER_ID=$(USER_ID) docker compose --env-file .env build

up:
	@echo "Starting containers"
	@USER_ID=$(USER_ID) docker compose --env-file .env up -d --remove-orphans

rebuild:
	@echo "Rebuilding containers"
	@USER_ID=$(USER_ID) docker compose up -d --build

stop:
	@echo "Stopping containers"
	@docker compose stop

shell:
	@docker exec -it $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.php-fpm) /bin/bash

composer-install:
	@echo "Running composer install"
	@docker exec -it $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.php-fpm) composer install

composer-update:
	@echo "Running composer install"
	@docker exec -it $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.php-fpm) composer update

restore-db:
	@echo "Restore database dump from file ${DB_DATABASE}.sql"
	@docker exec -i $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.mariadb) mariadb -u${DB_USERNAME} -p"${DB_PASSWORD}" ${DB_DATABASE} < ${DB_DATABASE}.sql

backup-db:
	@echo "Backup database to ${DB_DATABASE}_1.sql"
	@docker exec $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.mariadb) mariadb-dump -u${DB_USERNAME} -p"${DB_PASSWORD}" ${DB_DATABASE} > ${DB_DATABASE}_1.sql

prepare-dev:
	cp -R .docker/certbot/conf/live/test-app.loc .docker/certbot/conf/live/${APP_HOST}
	cp .docker/docker-compose.dev.yml ./docker-compose.override.yml

verify: verify-php verify-js

verify-php: test-db
	@echo "==> psalm"
	@docker exec $(PHP_CONTAINER) composer cs-check
	@echo "==> lint:container"
	@docker exec $(PHP_CONTAINER) php bin/console lint:container
	@echo "==> phpunit"
	@docker exec $(PHP_CONTAINER) php bin/phpunit

verify-js:
	@echo "==> eslint"
	@npm run lint
	@echo "==> vite build"
	@npm run build

# Recreates the test database from the migrations and loads tests/Fixtures.
test-db:
	@test -n "$(PHP_CONTAINER)" -a -n "$(DB_CONTAINER)" || { echo "The containers are not running, start them with 'make up'" >&2; exit 1; }
	@echo "==> test database"
	@docker exec $(DB_CONTAINER) sh -c 'mariadb -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON \`$${MYSQL_DATABASE}_test\`.* TO \"$$MYSQL_USER\"@\"%\""'
	@docker exec $(PHP_CONTAINER) php bin/console doctrine:database:drop --env=test --force --if-exists --quiet
	@docker exec $(PHP_CONTAINER) php bin/console doctrine:database:create --env=test --quiet
	@docker exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --env=test --no-interaction --quiet
	@docker exec $(PHP_CONTAINER) php bin/console doctrine:fixtures:load --env=test --no-interaction --quiet
