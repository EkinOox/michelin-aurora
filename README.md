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

Dans un terminal séparé (Python 3.x requis, aucune dépendance externe) :

```bash
python3 simulator/esp32_sim.py
```

Le script pousse des données de pression et de vitesse toutes les 2 secondes vers `POST /api/telemetry`.

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

*Document interne confidentiel · Version 2.1*
