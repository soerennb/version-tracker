# syntax=docker/dockerfile:1.7

FROM composer:2@sha256:a5f59b9fd2faf31218632be4809dc6491761085e8064c31dc3b84378c48c248b AS composer

FROM php:8.5.12-cli-bookworm@sha256:d4f6e10eb393de7e3a7a88e8b88e139072dd3ae297f6a5dada8257e6fe32c585 AS vendor

RUN apt-get update \
    && apt-get install --yes --no-install-recommends git libfreetype6-dev libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl mbstring pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && APP_ENV=production APP_DEBUG=false APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= php artisan package:discover --ansi --no-interaction \
    && APP_ENV=production APP_DEBUG=false APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= php artisan filament:assets --silent --no-interaction \
    && test -s bootstrap/cache/packages.php \
    && test -s bootstrap/cache/services.php \
    && test -s public/css/filament/filament/app.css \
    && test -s public/js/filament/filament/app.js

FROM node:26.12.1-bookworm-slim@sha256:28147155d47dc31abe0a46e0d12b59b2fb3d0a2dcc5dc65fce76a074180b16fe AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci --no-audit --fund=false

COPY --from=vendor /var/www/html/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.5.12-apache-bookworm@sha256:5e4418695f9b13b75edefaff547125bf6be267e865931731bdd5946461505736

RUN apt-get update \
    && apt-get install --yes --no-install-recommends curl libbrotli1 libexpat1 libfreetype6 libicu72 libjpeg62-turbo libonig5 libpng16-16 libzip4 \
    && a2enmod rewrite \
    && printf 'ServerName localhost\n' > /etc/apache2/conf-available/versiontracker-servername.conf \
    && a2enconf versiontracker-servername \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=vendor /var/www/html ./
COPY --from=frontend /var/www/html/public/build ./public/build
COPY --from=vendor /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY docker-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-versiontracker.ini

RUN docker-php-ext-enable gd intl pdo_mysql zip \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} + \
    && chmod 755 /var/www/html \
    && chown -R www-data:www-data storage bootstrap/cache

RUN test -s /var/www/html/public/build/manifest.json

EXPOSE 80
