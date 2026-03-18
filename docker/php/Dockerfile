FROM php:8.4-fpm-alpine

RUN set -eux; \
    apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        $PHPIZE_DEPS; \
    docker-php-ext-install \
        pdo \
        pdo_mysql \
        intl \
        zip \
        mbstring \
        opcache; \
    apk del --no-cache $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
