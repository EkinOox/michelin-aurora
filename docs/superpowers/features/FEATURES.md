# Aurora , Cycling Intelligence Platform
### Documentation des fonctionnalités · Michelin LB 2 Wheels Hackathon

---

## Contexte & problématique

Le cyclisme moderne exige bien plus qu'un simple vélo : sécurité active, performance optimisée, et expérience personnalisée sont devenus des attentes de fond. Pourtant, **la gestion des pneumatiques** , première interface entre le cycliste et la route, reste largement empirique. La pression se règle une fois, au ressenti, sans tenir compte de la météo, du terrain ni du profil du rider.

**Aurora** répond à cette problématique en transformant le pneumatique Michelin en capteur intelligent au cœur d'une expérience cycliste complète : recommandation proactive, suivi temps réel, communauté et valorisation de chaque kilomètre parcouru.

> Stack : **Nuxt 4** (frontend PWA) · **Symfony 7** (API REST + SSE) · **PostgreSQL** · **Docker** · **JWT Auth**

---

## 1. Profil cycliste personnalisé

**Page :** `/onboarding` · `/profile`

### Ce que ça fait
À l'inscription, le cycliste renseigne son type de vélo (Route, Gravel, VTT, VAE), son niveau (Débutant → Expert), son usage (Loisir, Performance, Compétition, Transport) et ses préférences de conduite. Il peut également uploader une photo de son vélo.

### Pourquoi
Sans connaissance du profil du rider, toute recommandation est générique et donc inutile. Ces données constituent le **socle de la personnalisation** : un débutant sur VTT n'a pas les mêmes besoins de pression qu'un expert en course sur route. Le profil est persisté côté serveur pour fonctionner sur tous les appareils de l'utilisateur.

---

## 2. Recommandation de pression dynamique

**Page :** `/pressure` · API : `GET /api/pressure`

### Ce que ça fait
Aurora calcule en temps réel la pression idéale avant/arrière pour les pneus du cycliste, en croisant :

- **Données météo live** (Open-Meteo API) : température, précipitations, humidité, vitesse du vent, code météo WMO
- **Profil du rider** : type de vélo (bases différentes par discipline) et niveau (ajustement ±0,10 bar)
- **Algorithme multi-facteurs** :
  - Pluie → baisse de pression (augmentation de la surface de contact = grip)
  - Froid → baisse (caoutchouc moins souple)
  - Chaleur → hausse (expansion thermique)
  - Vent fort → légère hausse (stabilité)

| Vélo   | Base avant | Base arrière |
|--------|-----------|-------------|
| Route  | 3,2 bar   | 3,5 bar     |
| Gravel | 2,6 bar   | 2,8 bar     |
| VTT    | 1,7 bar   | 1,9 bar     |
| VAE    | 2,8 bar   | 3,0 bar     |

Les facteurs d'ajustement sont exposés dans la réponse API pour garantir la transparence du calcul.

### Pourquoi
C'est **le cœur de la valeur produit Michelin** : démontrer que le pneumatique n'est pas une donnée fixe, mais un paramètre vivant. Adapter la pression à la météo réduit les risques de crevaison, optimise la performance et prolonge la durée de vie du pneu , trois arguments business directs pour Michelin.

---

## 3. Télémétrie temps réel (SSE)

**Page :** `/ride` · API : `POST /api/telemetry` · `GET /api/telemetry/stream`

### Ce que ça fait
Pendant une sortie active, Aurora stream en continu les données de télémétrie via **Server-Sent Events** :
- Pression avant/arrière (bar)
- Vitesse instantanée (km/h)
- Alertes de chute de pression
- Durée et distance calculées côté client

Quand une alerte est déclenchée, une **notification push** est créée automatiquement avec la valeur de pression incriminée et l'ID de la sortie.

### Pourquoi
Le SSE (plutôt que WebSocket) est un choix délibéré : **unidirectionnel, léger, natif HTTP**, compatible avec les proxies et les PWAs sans configuration spéciale. Il simule un capteur embarqué sur le pneu , la vision produit de Michelin pour les pneus connectés. Cette architecture démontre la faisabilité d'un vrai système de monitoring pneumatique en conditions réelles.

---

## 4. Suivi de sorties

**Page :** `/ride` · `/ride/alert` · API : `POST /api/rides`

### Ce que ça fait
Chaque sortie est une entité persistée : démarrage en un tap, suivi continu, arrêt et sauvegarde. Les métriques accumulées (km, durée, points) alimentent directement le système de récompenses. Le bouton d'alerte donne accès aux notifications de pression en cours de sortie.

### Pourquoi
Sans tracking de sorties, pas de données longitudinales sur l'usage des pneus. C'est la **brique de collecte** qui alimente les recommandations futures et légitime la gamification : les points récompensent de vrais kilomètres, pas des actions artificielles.

---

## 5. Ride Rewards , Système de gamification

**Page :** `/rewards` · API : `GET /api/rewards` · `POST /api/rewards/redeem/{id}`

### Ce que ça fait
Chaque kilomètre parcouru génère des points Aurora. Un système de **5 niveaux progressifs** récompense l'engagement :

| Rang               | Points requis | Couleur   |
|--------------------|--------------|-----------|
| Explorer           | 0            | Gris      |
| Rider              | 1 000        | Bleu      |
| Performer          | 2 500        | Vert lime |
| Elite Cyclist      | 4 000        | Or        |
| Michelin Ambassador| 8 000        | Rouge     |

Des **multiplicateurs** s'appliquent selon les conditions : météo difficile (×1,3), terrain technique (×1,2), sortie endurance +100 km (×1,5). Les points s'échangent contre des **codes de réduction** sur les produits Michelin (catalogue configurable).

### Pourquoi
La gamification transforme chaque sortie en opportunité de fidélisation. Le cycliste engagé devient ambassadeur Michelin naturel. Les codes de réduction créent un **lien direct entre l'application et l'achat de pneus** , modèle de monétisation indirecte mesurable pour Michelin.

---

## 6. Itinéraires personnalisés

**Page :** `/routes` · `/routes/[id]` · API : `GET /api/routes`

### Ce que ça fait
Aurora propose des itinéraires **filtrés par profil** (type de vélo, niveau) avec un score de compatibilité calculé (`match_score`). Chaque route expose :
- Distance, dénivelé, durée estimée
- Score Michelin, score sécurité, score plaisir
- Pneu recommandé pour la route
- Géométrie réelle chargée depuis **OpenStreetMap/Overpass API** et rendue sur carte interactive (MapLibre)
- Cache localStorage 1h (position) et 7 jours (géométrie OSM)

### Pourquoi
La recommandation d'itinéraire est indissociable de la recommandation pneumatique : **un gravel à 2 000 m de dénivelé n'appelle pas le même pneu qu'une sortie sur route plate**. Afficher le pneu recommandé par route est un vecteur de vente conseil direct, dans le contexte exact où le cycliste en a besoin.

---

## 7. Communauté & Système d'amis

**Page :** `/community` · API : `GET /api/community/users` · `POST/DELETE /api/community/friends/{id}`

### Ce que ça fait
- Liste des cyclistes de la plateforme avec filtres (tous / ma discipline / amis)
- Barre de recherche par nom
- Envoi / acceptation / refus / suppression de demandes d'amitié
- **Fiche rider** (bottom sheet) : photo de vélo, stats (km total, sorties, km ce mois), profil cycliste, bouton ami contextuel selon le statut de la relation
- Stats communauté : mon rang, points moyens, riders dans ma discipline

### Pourquoi
La dimension sociale ancre l'application dans le quotidien. Un cycliste qui voit ses amis rouler et progresser est un cycliste qui revient. Le système d'amis crée également un **graphe de données comportementales** utile pour des recommandations futures par affinité de profil.

---

## 8. Notifications multi-types

**Composant :** `NotificationsSheet` · API : `GET /api/notifications` · `DELETE /api/notifications/{id}`

### Ce que ça fait
Un centre de notifications unifié accessible depuis toutes les pages via la cloche (avec compteur de non-lus) :

- **Demandes d'amitié** : accepter / refuser directement depuis la notification
- **Alertes pneus** : chute de pression détectée pendant une sortie, avec valeur exacte et accès rapide au revendeur le plus proche
- **Informations système** : messages de la plateforme
- Chaque notification est supprimable individuellement
- Action "Tout lire" pour marquer toutes les notifications comme lues

### Pourquoi
Les alertes pneus sont **le cas d'usage le plus critique** : une chute de pression pendant une sortie peut être dangereuse. Notifier immédiatement le cycliste avec la valeur exacte et un accès direct au revendeur le plus proche, c'est transformer Aurora en **assistant de sécurité actif** , différenciant fort par rapport à des applications de tracking classiques.

---

## 9. Boutique & Revendeurs Michelin

**Page :** `/store` · `RetailerSheet` · API : `GET /api/tires` · `GET /api/retailers`

### Ce que ça fait
- Catalogue de pneus Michelin filtré selon le profil du cycliste, avec scores (grip, endurance, légèreté…), prix indicatif et durée de vie moyenne
- **Revendeur le plus proche** : localisation, horaires, statut de stock , accessible depuis la boutique, les alertes pneus et les événements
- **Click & Collect urgence** : en cas d'alerte pression, accès direct au revendeur en un tap

### Pourquoi
Fermer la boucle entre l'expérience utilisateur et l'acte d'achat est l'objectif business central de Michelin. Aurora ne se contente pas de recommander un pneu , elle **guide jusqu'au revendeur agréé le plus proche** au moment exact où le besoin est identifié (crevaison, fin de vie du pneu). Le modèle est comparable au "next best action" des plateformes e-commerce.

---

## 10. Événements cyclistes

**Page :** `/events` · API : `GET /api/events`

### Ce que ça fait
Catalogue d'événements (Route, Gravel, Endurance) avec date, lieu, distance, nombre de participants inscrits et km à parcourir depuis la position de l'utilisateur. Sur chaque événement : suggestion du pneu Michelin adapté à la discipline.

### Pourquoi
Les événements sont des moments d'achat fort : avant une course, le cycliste investit dans son équipement. Lier chaque événement à une recommandation de pneu crée un **contexte d'achat hautement qualifié**, avec un niveau d'intention bien supérieur à de la publicité générique.

---

## 11. PWA & Expérience mobile native

**Architecture :** Nuxt 4 PWA · manifest · service worker · safe-area CSS

### Ce que ça fait
Aurora est installable comme application native sur iOS et Android. L'UX est pensée mobile-first :
- **Bottom sheets** (Sheet component) : navigation contextuelle sans quitter la page (notifications, profil rider, articles, revendeurs) via `position: fixed` + `<Teleport to="body">`
- **Safe-area** : `env(safe-area-inset-bottom)` pour la barre d'accueil iPhone
- **dvh units** : hauteur de viewport dynamique, compatible avec le chrome Safari mobile
- **Tab bar** native en bas d'écran
- **Géolocalisation avec cache** : la position est mise en cache localStorage pour éviter les re-demandes de permission intempestives

### Pourquoi
Le cycliste utilise Aurora **pendant ou avant une sortie**, donc sur mobile, souvent en mouvement. Une application qui se comporte comme une app native (pas de rechargement de page, transitions fluides, installable) est un prérequis pour l'adoption. Une friction supplémentaire se traduit directement par de l'abandon.

---

## Architecture technique

```
┌─────────────────────────────────────────────┐
│  PWA Nuxt 4 (TypeScript)                    │
│  ├── Composables partagés (état global SSR) │
│  ├── Auto-imports (composants, pages)       │
│  └── $apiFetch / useApiFetch (JWT auto)     │
└──────────────────┬──────────────────────────┘
                   │ HTTPS / JWT Bearer
┌──────────────────▼──────────────────────────┐
│  API Symfony 7 (PHP 8.3)                    │
│  ├── Controllers REST + SSE stream          │
│  ├── Doctrine ORM (Entities, Repositories)  │
│  ├── JWT Auth (LexikJWTAuthBundle)          │
│  └── PHPUnit (tests unitaires + intégration)│
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│  PostgreSQL · Docker Compose · Nginx        │
└─────────────────────────────────────────────┘

APIs tierces :
  Open-Meteo   → météo temps réel (gratuit, RGPD-safe)
  Overpass API → géométrie des itinéraires (OpenStreetMap)
```

---

## Synthèse , Réponse à la problématique

| Problème identifié | Réponse Aurora |
|---|---|
| Pression réglée empiriquement | Recommandation dynamique météo + profil |
| Aucune détection de risque en roulant | Télémétrie SSE + alertes push temps réel |
| Absence de lien sortie → achat pneu | Boutique contextuelle + revendeur en un tap |
| Peu d'engagement post-achat | Gamification (points, rangs, récompenses) |
| Expérience générique, pas personnalisée | Profil cycliste → tout s'adapte |
| Cyclisme = activité solitaire | Communauté, système d'amis, événements |

Aurora transforme le pneu Michelin d'un **consommable passif** en **service actif** , une plateforme qui accompagne le cycliste avant, pendant et après chaque sortie, et qui ramène naturellement vers les produits et revendeurs Michelin au moment le plus pertinent.
