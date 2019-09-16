YESTERDAY=$(shell date -v-1d +"%Y-%m-%d")
BACKUP_LOCATION="smart:backup/snapshot/databases/${YESTERDAY}/*lidsys.sql.gz"

install: install-${ENV}

install-development: install-dev-db install-dev-config init
install-production: init

init:
	ln -sfn docker/docker-compose.${ENV}.yml docker-compose.yml
	docker-compose build
	docker-compose up -d
	docker-compose run --rm php-fpm composer install
	docker-compose run --rm php-fpm npm ci

install-dev-db:
	rsync -aP ${BACKUP_LOCATION} ./docker/mariadb/initdb.d/

install-dev-config:
	cp config/autoload/database.dev.php config/autoload/database.local.php
	cp config/autoload/debug.dev.php config/autoload/debug.local.php

npm-build:
	docker-compose run --rm php-fpm npm run build

watch: npm-build
	docker-compose run --rm php-fpm npm run watch
