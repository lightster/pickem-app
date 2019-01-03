FROM php:5.6-fpm-alpine

# git is for composer
# bcmath is for phpunit
# zip is for composer

# opcache is for lidsys-web
# pdo is for lidsys-web
# pdo_mysql is for lidsys-web
# nodejs is for lidsys-web JS/CSS assets
# dom is for lidsys-web

# needed by lidsys-web but not directly included:
# - mysqlnd is provided by the base php image

RUN apk add --no-cache --virtual '.lightster-phpize-deps' \
        $PHPIZE_DEPS \
    && apk add --no-cache \
        git \
        libxml2-dev \
        npm \
        zlib-dev \
    && docker-php-ext-install \
        bcmath \
        zip \
        opcache \
        pdo \
        pdo_mysql \
        dom \
    && apk del --no-cache .lightster-phpize-deps

RUN curl -sS https://getcomposer.org/installer >composer-setup.php \
    && php composer-setup.php --quiet \
        --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

WORKDIR /var/www/html/lidsys-web
