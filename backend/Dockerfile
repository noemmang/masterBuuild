FROM dunglas/frankenphp:1-php8.4-alpine

# Seguridad (CVE-2026-109618 / CVE-2026-109619, RCE sin autenticación):
# aborta el build si la imagen base todavía trae un PHP sin parchear, para
# que nunca se despliegue una imagen vulnerable. Cuando la base ya sea
# >= 8.4.27 el build pasa solo. Si algún día se cambia de rama (8.5...),
# actualizar el número.
RUN php -r 'if (version_compare(PHP_VERSION, "8.4.27", "<")) { fwrite(STDERR, "PHP ".PHP_VERSION." sin parchear: se requiere >= 8.4.27\n"); exit(1); } echo "PHP ".PHP_VERSION." OK\n";'

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