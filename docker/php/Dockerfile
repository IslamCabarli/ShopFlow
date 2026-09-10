FROM php:8.5-fpm-alpine

WORKDIR /var/www/html

# System dependencies needed to build the PHP extensions below.
# apk update is run as its own step first so a stale/broken index is
# easy to spot separately from an actual package install failure.
RUN apk update && apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    $PHPIZE_DEPS

# Core PHP extensions ShopFlow needs.
# opcache is installed in its own RUN step: combining it with the others in a
# single docker-php-ext-install call has been unreliable — it configures but
# never actually compiles, leaving modules/ empty and failing "make install".
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    bcmath \
    zip \
    mbstring \
    intl

RUN docker-php-ext-install opcache

# Redis extension (via PECL, not bundled with php.net images)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer (copied from the official composer image, no separate install script needed)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-shopflow.ini

EXPOSE 9000

CMD ["php-fpm"]
