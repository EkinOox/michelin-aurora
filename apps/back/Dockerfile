FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && echo "output_buffering = Off" > /usr/local/etc/php/conf.d/sse.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dev
COPY . .
RUN composer install --no-interaction --optimize-autoloader

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8080
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ── Image de production : deps sans dev, opcache, migrations au démarrage ──────
FROM base AS prod
ENV APP_ENV=prod

COPY . .
# --no-scripts : on évite les @auto-scripts (boot du kernel) au build ;
# le cache prod est (re)généré au démarrage par l'entrypoint avec les vrais secrets.
RUN composer install --no-interaction --no-dev --no-scripts --optimize-autoloader --classmap-authoritative

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php-prod.ini /usr/local/etc/php/conf.d/zz-prod.ini
COPY docker/entrypoint-prod.sh /usr/local/bin/entrypoint-prod.sh
RUN chmod +x /usr/local/bin/entrypoint-prod.sh \
    && mkdir -p var && chown -R www-data:www-data var

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint-prod.sh"]
