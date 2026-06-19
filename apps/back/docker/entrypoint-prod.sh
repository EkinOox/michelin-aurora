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

# Créer le répertoire d'uploads si absent (premier démarrage sur volume vierge).
mkdir -p public/uploads/bikes

# var/, config/jwt et public/uploads doivent être inscriptibles par www-data.
chown -R www-data:www-data var config/jwt public/uploads

# Migrations Doctrine (idempotent ; ne casse pas s'il n'y en a aucune à jouer).
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
