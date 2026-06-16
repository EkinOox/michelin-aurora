#!/bin/sh
set -e

# Cache prod (re)généré au démarrage avec les vraies variables d'env.
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# var/ doit être inscriptible par les workers php-fpm (www-data).
chown -R www-data:www-data var

# Migrations Doctrine (idempotent ; ne casse pas s'il n'y en a aucune à jouer).
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
