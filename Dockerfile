FROM composer:2 AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl

COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts

FROM node:24-bookworm-slim AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci --no-audit --fund=false

COPY --from=vendor /var/www/html/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install --yes --no-install-recommends curl libfreetype6-dev libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl mbstring pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /var/www/html ./
COPY --from=frontend /var/www/html/public/build ./public/build
COPY docker-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD curl --fail --silent http://localhost/up || exit 1

EXPOSE 80
