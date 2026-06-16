# Déploiement prod — Michelin Aurora

**Date:** 2026-06-16
**Statut:** Validé (architecture), prêt pour le plan d'implémentation

## Objectif

Mettre le projet en ligne sur le serveur Hetzner `167.233.142.20` en réutilisant
les images Docker déjà construites et publiées sur GHCR par le workflow CI/CD, avec
un reverse proxy nginx en frontal et un déploiement automatique après chaque push
sur `main`.

## Décisions validées

| Sujet | Décision |
|---|---|
| Accès | IP directe `167.233.142.20`, **HTTP uniquement** (pas de domaine, pas de TLS) |
| Images | Ajout d'un **target `prod`** aux deux Dockerfiles ; le CD push ces images prod |
| Déploiement | **Auto-deploy via GitHub Actions** (SSH depuis le workflow après le push des images) |
| Packages GHCR | **Publics** (pas de `docker login` requis sur le serveur) |
| Reverse proxy | **nginx** unique sur le port 80, routage par chemin |

## Architecture

Point d'entrée unique HTTP sur le port 80, routage par chemin :

```
Navigateur ──:80──> [proxy nginx]
                       ├── /api/*  ─────> back  (Symfony, nginx+fpm interne :8080)
                       └── /*      ─────> front (Nuxt Nitro :3000)

front (SSR) ──interne──> back:8080   (NUXT_API_BASE_INTERNAL)
back ──interne──> db:5432            (Postgres, non exposé publiquement)
```

Toutes les routes API Symfony sont déjà préfixées `/api` (vérifié dans
`apps/back/src/Controller/*`), donc `proxy_pass` vers `back:8080` en conservant
l'URI fonctionne sans réécriture.

Le front appelle l'API via `${apiBase}/api/...` (cf. `app/composables/useApiBase.ts`) :
- **côté navigateur** : `apiBase = NUXT_PUBLIC_API_BASE = http://167.233.142.20` → passe par le proxy `/api`
- **côté SSR** : `apiBase = NUXT_API_BASE_INTERNAL = http://back:8080` → appel interne direct

## Services — `docker-compose.prod.yml`

| Service | Image | Exposé | Détails |
|---|---|---|---|
| `proxy` | `nginx:alpine` | `80:80` | conf montée depuis `deploy/nginx/`, route `/api`→back, `/`→front. `proxy_buffering off` + `proxy_read_timeout` long sur `/api` pour l'endpoint SSE `/api/telemetry/stream` |
| `back` | `ghcr.io/ekinoox/michelin-aurora-back:latest` | interne | `APP_ENV=prod`, `APP_SECRET`, `DATABASE_URL` ; entrypoint joue les migrations Doctrine puis lance supervisor ; `depends_on db (healthy)` |
| `front` | `ghcr.io/ekinoox/michelin-aurora-front:latest` | interne | `NUXT_PUBLIC_API_BASE=http://167.233.142.20`, `NUXT_API_BASE_INTERNAL=http://back:8080` |
| `db` | `postgres:16-alpine` | interne | volume `pg_data`, **pas de port 5432 public**, healthcheck `pg_isready` |

Différences avec le `docker-compose.yml` de dev : pas de pgAdmin, pas de port DB
exposé, pas de montage du code en volume (on `pull` les images), `front` lancé en
serveur Nitro buildé (et non `npm run dev`).

## Images prod — nouveaux targets

### Front (`apps/front/Dockerfile`) — stage `prod`
- `npm run build` (génère `.output/` avec le serveur Nitro)
- image finale `node:22-bookworm-slim` minimale, **sans Playwright/Chromium** (réservé au dev/e2e)
- `CMD ["node", ".output/server/index.mjs"]`, `EXPOSE 3000`, `ENV HOST=0.0.0.0 PORT=3000`
- `NUXT_PUBLIC_API_BASE` reste surchargeable au runtime (clé présente dans `runtimeConfig.public`)

### Back (`apps/back/Dockerfile`) — stage `prod`
- `composer install --no-dev --optimize-autoloader --classmap-authoritative`
- `APP_ENV=prod`, opcache activé (conf PHP dédiée)
- réutilise le `nginx.conf` + `supervisord.conf` existants (nginx+fpm)
- **entrypoint** : `php bin/console doctrine:migrations:migrate --no-interaction` puis `supervisord`

## CI/CD — modifications `.github/workflows/cd.yml`

1. Build & push des deux images en **`target: prod`** (au lieu de `dev`). Tags `latest` + `sha-xxx` inchangés.
2. Nouveau job **`deploy`** (`needs: push-images`) :
   - copie `deploy/` (compose prod + conf nginx) vers `/opt/michelin-aurora/` sur le serveur (scp/rsync via SSH)
   - exécute `docker compose -f docker-compose.prod.yml --env-file .env.prod pull`
   - puis `... up -d`
   - puis `docker image prune -f`

Secrets GitHub requis : `SSH_HOST`, `SSH_USER`, `SSH_KEY` (clé SSH privée).

## Fichiers du repo (nouveaux / modifiés)

- `deploy/docker-compose.prod.yml` (nouveau)
- `deploy/nginx/default.conf` (nouveau — conf du proxy edge)
- `deploy/.env.prod.example` (nouveau — template des secrets serveur, sans valeurs réelles)
- `apps/front/Dockerfile` (ajout stage `prod`)
- `apps/back/Dockerfile` (ajout stage `prod` + entrypoint migrations)
- `.github/workflows/cd.yml` (target prod + job deploy)
- `README.md` (section déploiement)

## Setup serveur (one-time, manuel)

1. Installer Docker Engine + plugin compose.
2. Créer `/opt/michelin-aurora/` et y déposer `.env.prod` avec les **secrets réels**
   (`POSTGRES_*`, `APP_SECRET`, `DATABASE_URL`, `NUXT_PUBLIC_API_BASE`). Fichier
   **jamais commité**.
3. Générer une paire de clés SSH dédiée au déploiement ; déposer la clé publique
   dans `~/.ssh/authorized_keys` du serveur ; mettre la clé privée dans le secret
   GitHub `SSH_KEY`.
4. Packages GHCR rendus **publics** (Settings → Packages → Change visibility) →
   aucun `docker login` nécessaire sur le serveur.

## Sécurité — points relevés

- `apps/back/.env.local` contient actuellement le **mot de passe root du serveur en
  clair**. Le fichier est gitignoré (pas dans le repo), mais il faut : (a) passer au
  déploiement par **clé SSH**, (b) retirer ce mot de passe du fichier ensuite.
- Postgres et pgAdmin ne sont pas exposés publiquement en prod.
- CORS : le `nginx.conf` du back renvoie `Access-Control-Allow-Origin: *`. Acceptable
  pour un hackathon ; à restreindre si le projet va plus loin.

## Hors périmètre (YAGNI pour le hackathon)

- TLS/HTTPS et nom de domaine
- pgAdmin en prod
- Stratégie de backup base de données
- Rollback automatique / blue-green
