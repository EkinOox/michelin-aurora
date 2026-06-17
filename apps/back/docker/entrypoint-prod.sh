#!/bin/sh
set -e

# Cache prod (re)généré au démarrage avec les vraies variables d'env.
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# Clés JWT (Lexik) : générées une seule fois si absentes (passphrase = JWT_PASSPHRASE).
# config/jwt est un volume persistant → les clés survivent aux redéploiements.
if [ ! -f config/jwt/private.pem ]; then
    php bin/console lexik:jwt:generate-keypair --no-interaction --skip-if-exists
fi

# var/ et config/jwt doivent être lisibles/inscriptibles par les workers php-fpm (www-data).
chown -R www-data:www-data var config/jwt

# Migrations Doctrine (idempotent ; ne casse pas s'il n'y en a aucune à jouer).
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
