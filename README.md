# Michelin Aurora — Cycling Intelligence Platform

> Hackathon Skolae / ESGI 2026 · Équipe Innovation 5A — Ingénierie du Web  
> Client : **Michelin LB 2 Wheels** · Product Owner : Abdellatif GHACHI

---

## Présentation

Aurora transforme le pneu Michelin en **point de contact quotidien** entre la marque et le cycliste premium. La plateforme regroupe cinq modules :

| # | Module | Description |
|---|--------|-------------|
| M01 | Michelin Curated Routes | Générateur d'itinéraires personnalisés (Route / VTT / Gravel / VAE) |
| M02 | Dynamic Pressure Guide | Recommandation de pression idéale au PSI près selon la météo |
| M03 | Tableau de Bord & Télémétrie Live | Monitoring IoT temps réel via simulateur ESP32 |
| M04 | Michelin Ride Rewards | Programme de fidélité gamifié (Ride Points → codes promo) |
| M05 | Conversion Business & Upgrade Setup | Recommandations d'upgrade + Store Locator |

---

## Architecture

```
michelin-aurora/
├── apps/
│   ├── front/          # Nuxt 3 · Vue.js · TailwindCSS (PWA)
│   └── back/           # Symfony 7 · API Platform · Doctrine ORM
├── simulator/
│   └── esp32_sim.py    # Simulateur IoT ESP32 (Python)
├── docker-compose.yml  # 4 conteneurs : front · back · db · pgadmin
└── README.md
```

**4 conteneurs Docker :**

| Conteneur | Image | Port exposé |
|-----------|-------|-------------|
| `back` | PHP 8.4 + Nginx + Symfony 7 | `8080` |
| `db` | PostgreSQL 16 | `5432` |
| `pgadmin` | PgAdmin 4 | `5050` |
| `front` | Node 22 + Nuxt 3 | `3000` |

---

## Démarrage rapide

### Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) ≥ 28
- (optionnel) Node.js ≥ 22, PHP ≥ 8.4, Composer ≥ 2 pour le dev local sans Docker

### Lancer la stack complète

```bash
docker compose up --build
```

| Service | URL |
|---------|-----|
| Front-end Nuxt | <http://localhost:3000> |
| API Symfony | <http://localhost:8080/api/health> |
| PgAdmin | <http://localhost:5050> (admin@michelin-aurora.dev / aurora) |

### Simulateur IoT ESP32

D'abord, charger l'utilisateur et la sortie de démo (fixtures Doctrine) :

```bash
docker compose exec back php bin/console doctrine:fixtures:load --no-interaction
```

Puis, dans un terminal séparé (Python 3.x requis, aucune dépendance externe) :

```bash
API_BASE=http://localhost:8081 python3 simulator/esp32_sim.py
```

Le script récupère l'identifiant de la sortie de démo via `GET /api/rides/demo`, puis pousse des données de pression et de vitesse toutes les 2 secondes vers `POST /api/telemetry`. Ces données sont persistées et diffusées en direct (SSE) sur le tableau de bord live : <http://localhost:3001/dashboard>.

---

## Automatisation (Taskfile)

Les tâches courantes sont automatisées avec [go-task](https://taskfile.dev). Les commandes
back/front s'exécutent **dans les conteneurs Docker** : aucun PHP/Node requis en local.

### Installation de go-task

```bash
# Windows (winget)
winget install Task.Task
# ou via Scoop / Chocolatey : scoop install task | choco install go-task
```

### Tâches principales

```bash
task                 # liste toutes les tâches
task up              # démarre la stack (détaché)
task up:build        # démarre en (re)buildant les images
task down            # arrête la stack
task logs -- back    # logs suivis d'un service
task install         # installe les dépendances back + front
task test            # lance tous les tests (back PHPUnit + front Vitest)
task back:test       # tests PHPUnit uniquement
task front:test      # tests Vitest uniquement
task front:test:e2e  # tests end-to-end Playwright
task fixtures        # charge les fixtures Doctrine
task migrate         # applique les migrations Doctrine
task db:reset        # recrée la base + migrations + fixtures
task shell:back      # shell dans le conteneur back
task sim             # lance le simulateur IoT (Python, sur l'hôte)
```

> ℹ️ La stack doit être démarrée (`task up`) avant les tâches qui ciblent les conteneurs.

---

## Développement local (sans Docker)

### Back-end Symfony

```bash
cd apps/back
composer install
symfony server:start        # ou : php -S localhost:8080 -t public
```

### Front-end Nuxt

```bash
cd apps/front
npm install
npm run dev                 # http://localhost:3000
```

---

## Variables d'environnement

| Variable | Par défaut | Description |
|----------|-----------|-------------|
| `APP_SECRET` | `changeme_in_production` | Clé secrète Symfony |
| `DATABASE_URL` | *(défini dans docker-compose)* | DSN PostgreSQL |
| `NUXT_PUBLIC_API_BASE` | `http://localhost:8080` | URL de l'API Symfony |

Copier `apps/front/.env.example` → `apps/front/.env` pour le dev local.

---

## Stack technique

| Couche | Technologie | Justification |
|--------|-------------|---------------|
| Front-end | Nuxt 3 / Vue.js / TailwindCSS | SSR + CSR, PWA, réactivité native |
| Back-end | Symfony 7 / API Platform / Doctrine | Standard entreprise, robustesse, sécurité |
| Temps réel | SSE / WebSockets / Mercure | Latence < 50 ms pour la télémétrie |
| Base de données | PostgreSQL 16 | JSONB, scalabilité, relations complexes |
| DevOps | Docker / docker-compose | Portabilité locale ↔ CI/CD |
| Simulateur IoT | Python 3 | Prototype léger du firmware ESP32 |

---

## Déploiement (prod)

Le projet est servi sur **https://michelin-aurora.duckdns.org** (HTTPS, IP
`167.233.142.20`) via **Caddy**, qui gère le certificat Let's Encrypt
automatiquement et route `/api/*` vers le back Symfony, le reste vers le front Nuxt.

### Pipeline automatique
À chaque push sur `main`, GitHub Actions (`cd.yml`) :
1. build & push les images prod sur GHCR (`michelin-aurora-back`, `-front`) ;
2. copie `deploy/` sur le serveur en SSH ;
3. exécute `docker compose -f docker-compose.prod.yml --env-file .env.prod pull && up -d`.

### Setup serveur (une seule fois)
1. Installer Docker Engine + plugin compose.
2. `mkdir -p /opt/michelin-aurora` puis y créer `.env.prod`
   (copier `deploy/.env.prod.example`, renseigner les vrais secrets).
   `NUXT_PUBLIC_API_BASE=https://michelin-aurora.duckdns.org`.
3. Générer une clé SSH dédiée, ajouter la clé publique aux `authorized_keys`
   du serveur.
4. DNS : faire pointer le domaine (DuckDNS) vers l'IP du serveur, en
   enregistrement **A (IPv4) uniquement** — pas d'AAAA/IPv6 (Docker n'expose
   pas l'IPv6, sinon la validation Let's Encrypt échoue). Ports **80 et 443**
   ouverts.

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

---

*Document interne confidentiel · Version 2.1*
