# syntax=docker/dockerfile:1

# Versi dikunci (seri PHP 8.4), bukan tag yang mengambang.
# PHP 8.2 berakhir dukungan keamanannya 31 Des 2026; Laravel 13 mendukung PHP 8.3-8.5.
FROM php:8.4-cli-alpine

# git & unzip dipakai Composer. pdo_mysql dibangun dari sumber PHP, jadi
# alat kompilasi dipasang sementara (virtual package) lalu dibuang.
# $PHPIZE_DEPS sengaja tanpa tanda kutip: isinya daftar paket dipisah spasi.
# hadolint ignore=DL3018,SC2086
RUN apk add --no-cache git unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql \
    && apk del .build-deps

# Biner Composer dari image resminya (versi mayor dikunci).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Layer yang JARANG berubah di atas: composer.json dan composer.lock disalin
# dan dipasang SEBELUM kode aplikasi, supaya cache lapisan awet.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# Layer yang SERING berubah di bawah: kode aplikasi.
COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# TIDAK ada nilai rahasia di sini. APP_KEY, DB_PASSWORD, dll. disuntikkan
# lewat environment saat container dijalankan (lihat docker-compose.yml).
# --no-reload: agar variabel environment dari Docker dipakai apa adanya.
CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --force && exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload"]
