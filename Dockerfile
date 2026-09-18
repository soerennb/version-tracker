# syntax=docker/dockerfile:1.7

FROM composer:2@sha256:a5f59b9fd2faf31218632be4809dc6491761085e8064c31dc3b84378c48c248b AS composer

FROM php:8.5-cli-bookworm@sha256:2bdeae0060ab682b6ed133513a869168c92d751b81481c7f8c6c34875854d888 AS vendor

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
    && composer dump-autoload --no-dev --classmap-authoritative

FROM node:26-bookworm-slim@sha256:c8fedd782bcd1b68d8a7d1ed2577b5f820eba820871323f605292651ff11e3c6 AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci --no-audit --fund=false

COPY --from=vendor /var/www/html/vendor ./vendor
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.5-apache-bookworm@sha256:149e8051f063b00f71a9eb4a5779083e6383015702fc1f6139e58dad63b052eb

RUN apt-get update \
    && apt-get install --yes --no-install-recommends curl libfreetype6 libicu72 libjpeg62-turbo libonig5 libpng16-16 libzip4 \
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

EXPOSE 80
