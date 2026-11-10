# Imagem de produção: FrankenPHP serve o Laravel e cuida do HTTPS (Let's Encrypt) sozinho.
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

FROM dunglas/frankenphp:1.9-php8.4-bookworm
RUN install-php-extensions pdo_mysql intl opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/ruah.ini"

WORKDIR /app
COPY --from=vendor /app /app
COPY docker/entrypoint.sh /usr/local/bin/ruah-entrypoint
RUN chmod +x /usr/local/bin/ruah-entrypoint \
    && rm -f .env \
    && chown -R www-data:www-data storage bootstrap/cache

ENV SERVER_ROOT=/app/public
ENTRYPOINT ["ruah-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
