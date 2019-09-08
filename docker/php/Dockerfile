FROM php:7.3-fpm-alpine

# git is for composer
# bcmath is for phpunit
# zip is for composer

# opcache is for lidsys-web
# pdo_mysql is for lidsys-web
# nodejs is for lidsys-web JS/CSS assets

# pgsql is for pulse

# needed by lidsys-web but not directly included:
# - mysqlnd is provided by the base php image

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && apk add --no-cache \
        git \
        postgresql-dev \
        postgresql-client \
        libzip-dev \
        libxml2-dev \
        npm \
    && docker-php-ext-install \
        bcmath \
        opcache \
        pdo_mysql \
        pgsql \
        zip \
    && pecl install redis xdebug-2.7.1 \
    && docker-php-ext-enable redis xdebug \
    && apk del --no-cache .build-deps

RUN curl -sS https://getcomposer.org/installer >composer-setup.php \
    && php composer-setup.php --quiet \
        --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

WORKDIR /var/www/html/lidsys-web
