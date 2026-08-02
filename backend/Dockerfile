FROM dunglas/frankenphp:1-php8.4-alpine

# Copia del ejecutable de Composer desde su imagen oficial (no viene incluido en FrankenPHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    opcache \
    zip \
    intl

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && chmod -R 775 storage bootstrap/cache

COPY Caddyfile /Caddyfile

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

ENTRYPOINT ["/start.sh"]