# Déploiement prod (GHCR + nginx + auto-deploy) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mettre Michelin Aurora en ligne sur le serveur `167.233.142.20` (HTTP) en réutilisant les images GHCR, derrière un nginx unique, avec déploiement automatique sur push `main`.

**Architecture:** Deux nouvelles images prod (front Nitro buildé, back Symfony `--no-dev` + migrations au démarrage) publiées sur GHCR par le CD, tirées sur le serveur par un `docker-compose.prod.yml` où un `nginx:alpine` route `/api/*`→back et `/*`→front sur le port 80. Le job CD `deploy` copie les fichiers de déploiement par SSH puis fait `pull && up -d`.

**Tech Stack:** Docker multi-stage, Nuxt 4 (Nitro/node), Symfony 7 (PHP 8.4-fpm + nginx + supervisor), Postgres 16, nginx:alpine, GitHub Actions, GHCR.

**Note sur la nature « infra » de ce plan :** un Dockerfile/compose ne se teste pas en TDD unitaire. Le « test » de chaque tâche = build de l'image et/ou smoke test HTTP en local (Docker Desktop). Les étapes de vérification jouent ce rôle.

---

## File Structure

| Fichier | Rôle | Action |
|---|---|---|
| `apps/front/Dockerfile` | Ajout stages `build` + `prod` (Nitro) | Modifier |
| `apps/back/Dockerfile` | Ajout stage `prod` (composer --no-dev + entrypoint) | Modifier |
| `apps/back/docker/php-prod.ini` | Config opcache prod | Créer |
| `apps/back/docker/entrypoint-prod.sh` | Warmup + migrations + supervisor | Créer |
| `deploy/docker-compose.prod.yml` | Stack prod (pull GHCR, proxy, db) | Créer |
| `deploy/nginx/default.conf` | Reverse proxy edge (port 80) | Créer |
| `deploy/.env.prod.example` | Template des secrets serveur | Créer |
| `.gitignore` | Ignorer `deploy/.env.prod` réel | Modifier |
| `.github/workflows/cd.yml` | target `prod` + job `deploy` | Modifier |
| `README.md` | Section déploiement + setup serveur | Modifier |

---

## Task 1: Image prod du front (Nuxt → Nitro)

**Files:**
- Modify: `apps/front/Dockerfile`

- [ ] **Step 1: Remplacer le contenu du Dockerfile front**

Remplacer tout le contenu de `apps/front/Dockerfile` par :

```dockerfile
# Base Debian (glibc) plutôt qu'Alpine (musl) : requis pour les navigateurs Playwright.
FROM node:22-bookworm-slim AS base
WORKDIR /app

FROM base AS deps
COPY package.json ./
RUN npm install

FROM base AS dev
COPY --from=deps /app/node_modules ./node_modules
# Navigateur Chromium + dépendances système pour les tests e2e Playwright.
RUN npx playwright install --with-deps chromium
COPY . .
EXPOSE 3000
CMD ["npm", "run", "dev"]

# ── Build de production : génère .output/ (serveur Nitro autonome) ─────────────
FROM base AS build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ── Image de production : node minimal, sans Playwright ───────────────────────
FROM node:22-bookworm-slim AS prod
WORKDIR /app
ENV NODE_ENV=production
ENV HOST=0.0.0.0
ENV PORT=3000
# .output est autonome (embarque ses propres node_modules runtime).
COPY --from=build /app/.output ./.output
EXPOSE 3000
CMD ["node", ".output/server/index.mjs"]
```

- [ ] **Step 2: Builder l'image prod**

Run: `docker build --target prod -t aurora-front-prod "apps/front"`
Expected: build réussi, dernière ligne `naming to ... aurora-front-prod`.

- [ ] **Step 3: Smoke test du conteneur front**

Run :
```bash
docker run -d --name aurora-front-test -p 3000:3000 -e NUXT_PUBLIC_API_BASE=http://localhost aurora-front-prod
sleep 3
curl -sS -o /dev/null -w "%{http_code}" http://localhost:3000/
```
Expected: `200` (le HTML SSR est renvoyé ; les appels API échoueront sans back, c'est normal).

- [ ] **Step 4: Nettoyer**

Run: `docker rm -f aurora-front-test`
Expected: `aurora-front-test`

- [ ] **Step 5: Commit**

```bash
git add apps/front/Dockerfile
git commit -m "feat(front): ajoute target prod (build Nitro sans Playwright)"
```

---

## Task 2: Image prod du back (Symfony --no-dev + migrations)

**Files:**
- Create: `apps/back/docker/php-prod.ini`
- Create: `apps/back/docker/entrypoint-prod.sh`
- Modify: `apps/back/Dockerfile`

- [ ] **Step 1: Créer la config opcache prod**

Créer `apps/back/docker/php-prod.ini` :

```ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
realpath_cache_size=4096K
realpath_cache_ttl=600
output_buffering=Off
```

- [ ] **Step 2: Créer l'entrypoint prod**

Créer `apps/back/docker/entrypoint-prod.sh` (fins de ligne LF) :

```sh
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
```

- [ ] **Step 3: Ajouter le stage prod au Dockerfile back**

Ajouter à la fin de `apps/back/Dockerfile` (après le stage `dev`) :

```dockerfile

# ── Image de production : deps sans dev, opcache, migrations au démarrage ──────
FROM base AS prod
ENV APP_ENV=prod

COPY . .
RUN composer install --no-interaction --no-dev --optimize-autoloader --classmap-authoritative

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php-prod.ini /usr/local/etc/php/conf.d/zz-prod.ini
COPY docker/entrypoint-prod.sh /usr/local/bin/entrypoint-prod.sh
RUN chmod +x /usr/local/bin/entrypoint-prod.sh \
    && mkdir -p var && chown -R www-data:www-data var

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/entrypoint-prod.sh"]
```

- [ ] **Step 4: Builder l'image prod back**

Run: `docker build --target prod -t aurora-back-prod "apps/back"`
Expected: build réussi (le `composer install --no-dev` se termine sans erreur).

- [ ] **Step 5: Smoke test (sans DB — vérifie juste que l'image démarre jusqu'aux migrations)**

Le conteneur tentera les migrations et échouera faute de DB : c'est attendu. On vérifie seulement que le warmup passe et que l'échec vient bien de la connexion DB.

Run :
```bash
docker run --rm -e APP_SECRET=test -e DATABASE_URL="postgresql://x:x@nodb:5432/x?serverVersion=16" aurora-back-prod 2>&1 | head -20 || true
```
Expected: on voit la sortie du cache warmup, puis une erreur de connexion à `nodb` lors des migrations (preuve que l'entrypoint s'exécute correctement jusque-là). Le test complet avec DB est fait en Task 5.

- [ ] **Step 6: Commit**

```bash
git add apps/back/Dockerfile apps/back/docker/php-prod.ini apps/back/docker/entrypoint-prod.sh
git commit -m "feat(back): ajoute target prod (composer --no-dev, opcache, migrations au démarrage)"
```

---

## Task 3: Config nginx du reverse proxy edge

**Files:**
- Create: `deploy/nginx/default.conf`

- [ ] **Step 1: Créer la conf du proxy**

Créer `deploy/nginx/default.conf` :

```nginx
server {
    listen 80;
    server_name _;

    # API Symfony : on préserve le préfixe /api (proxy_pass sans URI).
    # proxy_buffering off + timeout long pour l'endpoint SSE /api/telemetry/stream.
    location /api/ {
        proxy_pass http://back:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
        proxy_read_timeout 3600s;
    }

    # Tout le reste : front Nuxt (serveur Nitro).
    location / {
        proxy_pass http://front:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

- [ ] **Step 2: Vérifier la syntaxe nginx**

Run :
```bash
docker run --rm -v "$(pwd)/deploy/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro" nginx:alpine nginx -t
```
Expected: `syntax is ok` et `test is successful` (les warnings sur `back`/`front` non résolus sont normaux hors réseau compose).

- [ ] **Step 3: Commit**

```bash
git add deploy/nginx/default.conf
git commit -m "feat(deploy): conf nginx reverse proxy (api->back, reste->front)"
```

---

## Task 4: docker-compose.prod.yml, template d'env et gitignore

**Files:**
- Create: `deploy/docker-compose.prod.yml`
- Create: `deploy/.env.prod.example`
- Modify: `.gitignore`

- [ ] **Step 1: Créer le compose de prod**

Créer `deploy/docker-compose.prod.yml` :

```yaml
services:

  # ── Reverse proxy edge : seul service exposé publiquement (port 80) ─────────
  proxy:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - back
      - front
    restart: unless-stopped
    networks:
      - aurora

  # ── Back-End Symfony (image GHCR) ──────────────────────────────────────────
  back:
    image: ghcr.io/ekinoox/michelin-aurora-back:latest
    environment:
      APP_ENV: prod
      APP_SECRET: ${APP_SECRET}
      DATABASE_URL: ${DATABASE_URL}
    depends_on:
      db:
        condition: service_healthy
    restart: unless-stopped
    networks:
      - aurora

  # ── Front-End Nuxt (image GHCR) ────────────────────────────────────────────
  front:
    image: ghcr.io/ekinoox/michelin-aurora-front:latest
    environment:
      NUXT_PUBLIC_API_BASE: ${NUXT_PUBLIC_API_BASE}
      NUXT_API_BASE_INTERNAL: http://back:8080
    depends_on:
      - back
    restart: unless-stopped
    networks:
      - aurora

  # ── PostgreSQL (non exposé publiquement) ───────────────────────────────────
  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ${POSTGRES_DB}
      POSTGRES_USER: ${POSTGRES_USER}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    volumes:
      - pg_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER} -d ${POSTGRES_DB}"]
      interval: 5s
      timeout: 5s
      retries: 10
    restart: unless-stopped
    networks:
      - aurora

volumes:
  pg_data:

networks:
  aurora:
    driver: bridge
```

- [ ] **Step 2: Créer le template d'env**

Créer `deploy/.env.prod.example` :

```dotenv
# Copier en .env.prod sur le serveur et renseigner les VRAIES valeurs.
# Ce fichier .example est commité ; .env.prod ne l'est JAMAIS.

# ── Symfony ───────────────────────────────────────────────────────────────────
APP_SECRET=change-me-32-hex-chars

# ── PostgreSQL ────────────────────────────────────────────────────────────────
POSTGRES_DB=aurora
POSTGRES_USER=aurora
POSTGRES_PASSWORD=change-me
DATABASE_URL=postgresql://aurora:change-me@db:5432/aurora?serverVersion=16

# ── Front : URL publique vue par le navigateur (IP du serveur, port 80) ───────
NUXT_PUBLIC_API_BASE=http://167.233.142.20
```

- [ ] **Step 3: Ignorer le .env.prod réel**

Ajouter à la fin de la section secrets de `.gitignore` (après la ligne `apps/back/.env.local`) :

```gitignore
# Secrets de prod côté serveur (jamais commités) ; seul .env.prod.example l'est.
/deploy/.env.prod
```

- [ ] **Step 4: Valider le compose**

Run :
```bash
docker compose -f deploy/docker-compose.prod.yml --env-file deploy/.env.prod.example config >/dev/null && echo OK
```
Expected: `OK` (la config est valide et les variables se substituent).

- [ ] **Step 5: Vérifier que .env.prod est bien ignoré**

Run :
```bash
cp deploy/.env.prod.example deploy/.env.prod && git check-ignore deploy/.env.prod
```
Expected: `deploy/.env.prod` (donc ignoré). Puis le retirer : `rm deploy/.env.prod`

- [ ] **Step 6: Commit**

```bash
git add deploy/docker-compose.prod.yml deploy/.env.prod.example .gitignore
git commit -m "feat(deploy): compose prod (pull GHCR), template env et gitignore"
```

---

## Task 5: Smoke test end-to-end local (stack complète)

**Files:** aucun fichier modifié — vérification uniquement.

But : prouver que les 3 services (proxy/front/back/db) fonctionnent ensemble, routing `/api` et `/` compris, avant de toucher au CI.

- [ ] **Step 1: Builder les images aux noms attendus par le compose**

Run :
```bash
docker build --target prod -t ghcr.io/ekinoox/michelin-aurora-back:latest "apps/back"
docker build --target prod -t ghcr.io/ekinoox/michelin-aurora-front:latest "apps/front"
```
Expected: deux builds réussis.

- [ ] **Step 2: Préparer un .env.prod local**

Run :
```bash
cp deploy/.env.prod.example deploy/.env.prod
```
Puis éditer `deploy/.env.prod` : mettre `NUXT_PUBLIC_API_BASE=http://localhost` et un `APP_SECRET`/`POSTGRES_PASSWORD` quelconques cohérents avec `DATABASE_URL`.

- [ ] **Step 3: Démarrer la stack**

Run :
```bash
docker compose -f deploy/docker-compose.prod.yml --env-file deploy/.env.prod up -d
```
Expected: les 4 services démarrent ; `back` joue les migrations puis reste up (vérifier avec `docker compose -f deploy/docker-compose.prod.yml ps`).

- [ ] **Step 4: Tester le routing API via le proxy**

Run :
```bash
curl -sS http://localhost/api/health
```
Expected: réponse JSON de `HealthController` (HTTP 200) — la requête a traversé proxy → back.

- [ ] **Step 5: Tester le front via le proxy**

Run :
```bash
curl -sS -o /dev/null -w "%{http_code}" http://localhost/
```
Expected: `200` — proxy → front (Nitro).

- [ ] **Step 6: Arrêter et nettoyer**

Run :
```bash
docker compose -f deploy/docker-compose.prod.yml --env-file deploy/.env.prod down -v
rm deploy/.env.prod
```
Expected: stack arrêtée, volumes supprimés, `.env.prod` local retiré.

- [ ] **Step 7: (pas de commit — vérification seule)**

---

## Task 6: CD — build target prod + job de déploiement SSH

**Files:**
- Modify: `.github/workflows/cd.yml`

- [ ] **Step 1: Passer les deux builds en target prod**

Dans `.github/workflows/cd.yml`, remplacer les **deux** occurrences de `target: dev` (étapes « Build & push back » et « Build & push front ») par `target: prod`.

- [ ] **Step 2: Ajouter le job de déploiement**

Ajouter, à la fin de `.github/workflows/cd.yml`, ce nouveau job (au même niveau d'indentation que `push-images:`) :

```yaml
  deploy:
    name: Deploy to server
    runs-on: ubuntu-latest
    needs: push-images
    steps:
      - uses: actions/checkout@v4

      - name: Copy deploy files to server
        uses: appleboy/scp-action@v0.1.7
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_KEY }}
          source: "deploy/docker-compose.prod.yml,deploy/nginx/default.conf"
          target: "/opt/michelin-aurora"
          strip_components: 1

      - name: Pull & restart on server
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /opt/michelin-aurora
            docker compose -f docker-compose.prod.yml --env-file .env.prod pull
            docker compose -f docker-compose.prod.yml --env-file .env.prod up -d
            docker image prune -f
```

Note : `strip_components: 1` dépose les fichiers en `/opt/michelin-aurora/docker-compose.prod.yml` et `/opt/michelin-aurora/nginx/default.conf` (le chemin attendu par le volume du compose). Le `.env.prod` reste sur le serveur, jamais écrasé.

- [ ] **Step 3: Vérifier la syntaxe YAML du workflow**

Run :
```bash
python -c "import yaml,sys; yaml.safe_load(open('.github/workflows/cd.yml')); print('YAML OK')"
```
Expected: `YAML OK`.

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/cd.yml
git commit -m "ci(cd): build images en target prod + job deploy SSH"
```

---

## Task 7: Documentation du déploiement (README)

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Ajouter une section déploiement**

Ajouter à la fin de `README.md` :

```markdown
## Déploiement (prod)

Le projet tourne sur `167.233.142.20` (HTTP, port 80) via un reverse proxy nginx
qui route `/api/*` vers le back Symfony et le reste vers le front Nuxt.

### Pipeline automatique
À chaque push sur `main`, GitHub Actions (`cd.yml`) :
1. build & push les images prod sur GHCR (`michelin-aurora-back`, `-front`) ;
2. copie `deploy/` sur le serveur en SSH ;
3. exécute `docker compose -f docker-compose.prod.yml --env-file .env.prod pull && up -d`.

### Setup serveur (une seule fois)
1. Installer Docker Engine + plugin compose.
2. `mkdir -p /opt/michelin-aurora` puis y créer `.env.prod`
   (copier `deploy/.env.prod.example`, renseigner les vrais secrets).
   `NUXT_PUBLIC_API_BASE=http://167.233.142.20`.
3. Générer une clé SSH dédiée, ajouter la clé publique aux `authorized_keys`
   du serveur.

### Secrets GitHub à configurer
`SSH_HOST` = `167.233.142.20`, `SSH_USER` = `root`, `SSH_KEY` = clé SSH privée.

### Packages GHCR
Rendre `michelin-aurora-back` et `michelin-aurora-front` **publics**
(Repo → Packages → Package settings → Change visibility) pour éviter tout
`docker login` sur le serveur.

### Déploiement manuel (dépannage)
```bash
cd /opt/michelin-aurora
docker compose -f docker-compose.prod.yml --env-file .env.prod pull
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d
```
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: section déploiement prod dans le README"
```

---

## Task 8: Checklist manuelle (hors code) — à cocher par l'humain

Ces actions ne sont pas automatisables depuis le repo ; les réaliser après le merge.

- [ ] Rendre les 2 packages GHCR **publics**.
- [ ] Sur le serveur : installer Docker, créer `/opt/michelin-aurora/.env.prod` avec les vrais secrets (`NUXT_PUBLIC_API_BASE=http://167.233.142.20`).
- [ ] Générer la clé SSH de déploiement, déposer la publique sur le serveur.
- [ ] Ajouter les secrets GitHub `SSH_HOST`, `SSH_USER`, `SSH_KEY`.
- [ ] 🔒 Retirer le mot de passe root en clair de `apps/back/.env.local` une fois la clé SSH en place.
- [ ] Pousser sur `main` et vérifier le run GitHub Actions (jobs `push-images` puis `deploy`).
- [ ] Vérifier `http://167.233.142.20/` (front) et `http://167.233.142.20/api/health` (back).

---

## Self-Review

**Spec coverage :**
- Accès IP/HTTP → Task 3 (nginx :80), Task 4 (proxy seul exposé). ✓
- Target `prod` images → Task 1 (front), Task 2 (back). ✓
- Auto-deploy GitHub Actions → Task 6. ✓
- Packages publics → Task 7 + Task 8. ✓
- Reverse proxy nginx routage par chemin → Task 3. ✓
- SSE `/api/telemetry/stream` (`proxy_buffering off`) → Task 3. ✓
- Migrations Doctrine au démarrage → Task 2 (entrypoint). ✓
- DB/pgAdmin non exposés → Task 4 (pas de ports, pas de pgAdmin). ✓
- `.env.prod` template + gitignore → Task 4. ✓
- Point sécurité (mot de passe root, clé SSH) → Task 8. ✓

**Placeholder scan :** aucun « TBD/TODO » ; tous les fichiers ont leur contenu complet.

**Type consistency :** noms d'images (`ghcr.io/ekinoox/michelin-aurora-back|front:latest`) identiques entre Task 4, Task 5 et le CD ; chemins de volume nginx (`./nginx/default.conf`) cohérents entre Task 3, Task 4 et Task 6 (`strip_components: 1`) ; variables d'env (`APP_SECRET`, `DATABASE_URL`, `NUXT_PUBLIC_API_BASE`, `POSTGRES_*`) identiques entre `.env.prod.example` et le compose.
