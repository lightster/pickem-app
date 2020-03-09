YESTERDAY=$(shell date -v-1d +"%Y-%m-%d")
BACKUP_LOCATION="${HOME}/Dropbox/Apps/Pickem/postgres-snapshots/latest/pickem.pgc"

install: install-${ENV}

install-development: install-dev-db install-dev-config init
install-production: init

init:
	ln -sfn docker/docker-compose.${ENV}.yml docker-compose.yml
	docker-compose build
	docker-compose up -d --remove-orphans
	docker-compose run --rm php-fpm composer install
	docker-compose run --rm -e COMPOSER=the-composer.json php-fpm composer install
	docker-compose run --rm php-fpm npm ci
	docker-compose run --rm php-fpm bin/the migrate:setup
	docker-compose run --rm php-fpm bin/the migrate
	docker-compose run --rm php-fpm php bin/app asset:generate-team-color-styles
	docker-compose run --rm php-fpm npm run prod

install-dev-db:
	rsync -aP ${BACKUP_LOCATION} ./docker/postgres/initdb.d/
	docker-compose run --rm postgres pg_restore -F c --no-owner --no-privileges -f \
		/docker-entrypoint-initdb.d/pickem.sql \
		/docker-entrypoint-initdb.d/pickem.pgc

install-dev-config:
	cp config/autoload/debug.dev.php config/autoload/debug.local.php

watch:
	rm -rf asset_manifest.json
	docker-compose run --rm php-fpm php bin/app asset:generate-team-color-styles
	docker-compose run --rm php-fpm npm run prod
	docker-compose run --rm php-fpm npm run watch
