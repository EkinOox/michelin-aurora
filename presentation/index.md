# Script de présentation — Aurora by Michelin

> **Contexte à garder en tête en permanence**
> Le client (Michelin LB 2 Wheels, via Abdellatif Ghachi) **ne nous a PAS donné de cahier des charges fonctionnel**. Il nous a donné un **objectif business** : *« donner de la visibilité à la marque et créer de l'appétence auprès d'une clientèle premium »*. Il attend de nous qu'on **transforme cet objectif flou en une solution concrète, justifiée et crédible**.
> → Tout le discours doit donc marteler : **« Voici le problème qu'on a su lire derrière votre objectif, voici la solution qu'on a inventée, et voici pourquoi elle répond précisément à votre besoin. »**
> Nous ne sommes pas des exécutants — nous sommes une **équipe produit** qui a fait des choix.

**Durée cible : ~10 min de pitch + 5 min de démo. 3 présentateurs.**
**Critères jury (à rappeler implicitement) :** Pertinence de la proposition ×4 · Architecture ×3 · Qualité code ×3 · UX/UI ×3 · CI/CD ×2 · Acquisition ×2.

---

## Slide 01 — Cover (AURORA)
**Qui :** Kyllian · **Durée :** 30 s · **Objectif :** poser le ton, donner envie.

**À dire :**
> « Bonjour, nous sommes Kyllian, Melwin et Jeremy. Vous nous avez confié un objectif : rendre Michelin LB 2 Wheels visible et désirable auprès des cyclistes premium. Vous ne nous avez pas dit *comment*. Alors on a conçu une réponse complète, et elle s'appelle **Aurora** — la *Cycling Intelligence Platform* qui transforme votre pneu, aujourd'hui un simple consommable, en **service actif utilisé à chaque sortie**. »

**Ne pas oublier :** prononcer "Aurora by Michelin", pas juste "notre app". Ancrer la marque dès la première phrase.

---

## Slide 02 — L'équipe
**Qui :** Kyllian · **Durée :** 30 s · **Objectif :** crédibilité + gestion de projet.

**À dire :**
> « Trois développeurs, des rôles clairs et complémentaires. Moi sur l'architecture front et l'infra, Melwin sur les parcours utilisateur et la gamification, Jeremy sur la donnée — l'algorithme de pression et l'intégration météo. On s'est réparti le travail par domaine pour avancer en parallèle sans se marcher dessus. »

**Ne pas oublier :** insister sur "complémentaires" et "en parallèle" → ça montre qu'on a géré le projet, pas juste codé. Rester court, ne pas lire les sous-tâches une par une.

---

## Slide 03 — La problématique client
**Qui :** Melwin · **Durée :** 1 min 15 · **Objectif :** LE slide le plus important (pertinence ×4). Prouver qu'on a *compris* le besoin avant de proposer.

**À dire :**
> « Repartons de votre brief : *visibilité* et *appétence*. Derrière ces deux mots, on a identifié deux vrais problèmes.
> **Un —** aujourd'hui Michelin vend un pneu premium… puis disparaît. Aucun point de contact digital, aucune relation après l'achat. La marque s'éteint dès que le client sort du magasin.
> **Deux —** le cycliste premium ne veut pas juste un consommable, il veut un **partenaire de performance**. Sans service à valeur ajoutée, Michelin reste une marque de pneus parmi d'autres.
> Notre réponse tient en une phrase : **Aurora place Michelin au cœur de chaque sortie.** Le pneu devient un service vivant, visible tous les jours. »

**Ne pas oublier :** c'est ici qu'on gagne ou qu'on perd le jury. Ralentir, regarder Abdellatif. Le mot "partenaire de performance" est la clé du positionnement premium.

---

## Slide 04 — Notre solution
**Qui :** Melwin · **Durée :** 1 min · **Objectif :** montrer que la solution couvre les 2 problèmes par 3 piliers.

**À dire :**
> « Aurora s'articule autour de trois piliers.
> **L'intelligence pneumatique** — la pression idéale calculée en temps réel selon la météo, le vélo et le niveau, avec alerte immédiate si une chute de pression est détectée. C'est ça qui rend Michelin *utile au quotidien*.
> **L'expérience cycliste complète** — sorties trackées, itinéraires, communauté, événements. C'est ça qui rend l'app *addictive* et donc *visible*.
> **L'engagement Michelin** — gamification, codes réduction sur les pneus, revendeur agréé à portée de clic. C'est ça qui *fidélise* et ramène à l'achat. »

**Ne pas oublier :** relier chaque pilier à un mot du brief — *utile* / *visible* / *fidélise*. Ne pas réciter des features, vendre des bénéfices.

---

## Slide 05 — Priorisation MVP
**Qui :** Melwin · **Durée :** 50 s · **Objectif :** montrer la rigueur produit (on a priorisé, pas tout fait au hasard).

**À dire :**
> « On a travaillé en MVP. À gauche, le cœur livré en priorité : authentification, profil, pression dynamique, suivi de sortie — sans ça, pas de produit.
> À droite, ce qu'on a livré **au-delà** du MVP parce qu'on avait de l'avance : la télémétrie temps réel, les récompenses, la communauté, la boutique. Au total **11 fonctionnalités**. On a su prioriser *puis* dépasser le périmètre. »

**Ne pas oublier :** le message c'est "discipline + ambition". On n'a pas codé dans le désordre, on a sécurisé le socle avant d'enrichir.

---

## Slide 06 — Architecture Back / Front
**Qui :** Kyllian · **Durée :** 1 min · **Objectif :** architecture ×3. Montrer une stack pro et séparée.

**À dire :**
> « Architecture en trois couches nettement séparées.
> Le **front**, une PWA Nuxt 4 en TypeScript, installable comme une vraie app mobile.
> Le **back**, une API Symfony 7 en PHP 8.3 — REST classique, plus un flux SSE pour la télémétrie temps réel. La communication est sécurisée par JWT.
> L'**infrastructure**, entièrement conteneurisée : Docker Compose, PostgreSQL, Nginx, déployée via GitHub Actions.
> Et trois intégrations externes : Open-Meteo pour la météo live, Overpass/OpenStreetMap pour les itinéraires, JWT stateless pour l'auth. »

**Ne pas oublier :** appuyer sur "séparées" et "stateless" → ça parle scalabilité. Ne pas plonger dans le détail de chaque techno, c'est le slide suivant.

---

## Slide 07 — Choix techniques clés
**Qui :** Kyllian · **Durée :** 1 min 10 · **Objectif :** qualité code/archi ×3. Montrer qu'on a fait des choix *justifiés*, pas suivi une mode.

**À dire :**
> « Quelques décisions qu'on assume.
> **SSE plutôt que WebSocket** : la télémétrie va dans un seul sens, du serveur vers le client. SSE est natif HTTP, zéro config proxy — parfait pour simuler un capteur de pneu connecté.
> **Nuxt 4 en SSR hybride** : premier rendu côté serveur pour le SEO et la perf, puis bascule client.
> **Un algorithme de pression multi-facteurs** : une base par discipline, ajustée par température, pluie, vent et niveau du rider — et on expose chaque facteur dans l'API pour la transparence.
> **JWT stateless, cache GPS stratifié** : pour ne jamais re-demander la permission de localisation pendant une sortie. »

**Ne pas oublier :** choisir 2-3 cartes à développer, survoler les autres. Le jury veut entendre un *pourquoi*, pas un catalogue. La phrase "on expose chaque facteur" montre la maturité.

---

## Slide 08 — CI/CD GitHub Actions
**Qui :** Kyllian · **Durée :** 45 s · **Objectif :** CI/CD ×2. Montrer l'automatisation et la non-régression.

**À dire :**
> « Chaque push déclenche le pipeline : tests PHPUnit, build, puis déploiement Docker automatique. Les tests couvrent le cœur métier — l'algorithme de pression dans ses cas gravel, pluie et sec, les alertes de télémétrie, les garde-fous d'entités. Résultat : un **pipeline vert** et zéro régression non détectée depuis qu'on l'a mis en place. »

**Ne pas oublier :** insister sur le fait que les tests portent sur le **métier critique** (la pression), pas sur du décoratif.

---

## Slide 09 — Design System Aurora
**Qui :** Melwin · **Durée :** 50 s · **Objectif :** UX/UI ×3.

**À dire :**
> « Côté expérience, tout est pensé mobile-first. Une vraie PWA installable iOS et Android. Des bottom sheets natifs pour consulter une info sans quitter la page. Un design system cohérent — tokens couleur Michelin, composants réutilisables. Et le souci du détail : gestion de la safe-area iPhone, du home indicator, testé sur device réel. Neuf pages, une seule identité visuelle. »

**Ne pas oublier :** dire "couleurs Michelin" (bleu + jaune). Mentionner "testé sur iPhone réel" → ça fait sérieux. C'est aussi un bon moment pour teaser la démo.

---

## Slide 10 — Go-to-market & Acquisition
**Qui :** Jeremy · **Durée :** 1 min · **Objectif :** acquisition ×2. Montrer qu'on pense business, pas que code.

**À dire :**
> « Comment on amène les utilisateurs ? Deux canaux qui se renforcent.
> **Digital** : SEO longue traîne sur les recherches "pression pneu vélo", réseaux sociaux, et un programme d'ambassadeurs intégré directement dans l'app via les rangs.
> **Physique — et c'est la force de Michelin** : le réseau de revendeurs agréés. Un QR code en boutique, l'installation proposée à l'achat d'un pneu, une présence sur les événements cyclistes.
> Le tout forme une **boucle** : alerte pression → revendeur → achat → points → montée en rang → nouvel achat. Michelin reprend la main sur toute la chaîne. »

**Ne pas oublier :** la "boucle" est l'argument fort — c'est ce qui transforme une app gadget en levier commercial. Le réseau physique est l'atout que les concurrents pure-players n'ont pas.

---

## Slide 11 — Livrables réalisés
**Qui :** Jeremy · **Durée :** 40 s · **Objectif :** prouver que le brief est couvert à 100 %.

**À dire :**
> « Concrètement, qu'est-ce qu'on vous remet ? À gauche le technique : une application **déployée et en ligne**, des tests à trois niveaux, la conteneurisation Docker, le pipeline CI/CD. À droite la documentation : guide développeur, documentation utilisateur, maquettes et parcours, code source propre et versionné. **Le brief est couvert intégralement.** »

**Ne pas oublier :** "déployée et en ligne" = on peut tester maintenant. C'est la transition naturelle vers la démo.

---

## Slide 12 — Ce que nous pourrions améliorer
**Qui :** Jeremy · **Durée :** 45 s · **Objectif :** montrer la vision long terme et l'honnêteté (on connaît nos limites).

**À dire :**
> « On ne s'arrête pas là. Si on avait plus de temps : le tracking GPS live avec courbe de pression sur le profil, des recommandations par Machine Learning, un vrai capteur Bluetooth pour remplacer notre simulateur, l'analyse post-sortie, l'intégration Strava et Komoot, une marketplace intégrée.
> Et le point important : ces évolutions sont **déjà dans notre backlog**, et notre architecture — API REST, SSE, composables — est conçue pour les accueillir **sans refonte**. »

**Ne pas oublier :** cadrer ça en **force**, pas en aveu de faiblesse. "On sait où on va, et l'archi est prête." Le simulateur Python actuel → vrai capteur, ça montre qu'on a anticipé.

---

## Slide 13 — Démonstration
**Qui :** les 3, Melwin pilote l'app · **Durée :** 5 min · **Objectif :** prouver que tout fonctionne pour de vrai.

**À dire (intro) :**
> « Assez parlé — regardons Aurora en conditions réelles. C'est en ligne, sur michelin-aurora.duckdns.org. »

**Parcours à dérouler (7 étapes, dans l'ordre des chips) :**
1. **Inscription** — création de compte en quelques secondes.
2. **Onboarding** — choix du vélo, niveau, usage → l'app se personnalise.
3. **Home & Pression** — montrer le cadran de pression qui réagit à la météo live du jour.
4. **Sortie live** — lancer une sortie, montrer la télémétrie temps réel (SSE).
5. **Alerte & Notifs** — déclencher l'alerte de chute de pression → proposition du revendeur Michelin le plus proche.
6. **Récompenses** — points gagnés, montée de rang, code promo pneu.
7. **Communauté** — profil rider, amis, dimension sociale.

**Ne pas oublier :**
- Lancer le **simulateur de capteur** avant la démo pour que la télémétrie soit vivante.
- Avoir un compte de secours déjà créé au cas où l'inscription bug.
- Terminer sur l'alerte → revendeur : c'est LA boucle business du slide 10 qui se matérialise à l'écran. C'est l'image qu'on veut laisser au jury.

---

## Phrase de clôture (après la démo)
**Qui :** Kyllian.
> « Vous nous avez donné un objectif sans solution. On vous rend une plateforme complète, déployée, testée et documentée, qui rend Michelin **visible et désirable à chaque coup de pédale**. Aurora ne vend pas un pneu — elle fait vivre la marque Michelin. Merci. »

---

## Anticipation des questions du jury

- **« Pourquoi une app et pas un simple site ? »** → Le cycliste l'utilise en mouvement, hors-ligne, sur mobile. La PWA est installable, fonctionne sans store, et reste indexable pour le SEO. Le meilleur des deux mondes.
- **« Le capteur de pression, c'est réel ? »** → Aujourd'hui simulé par un script Python qui pousse en SSE, exactement comme le ferait un module BLE. L'architecture est déjà prête à brancher du vrai matériel (slide 12).
- **« En quoi c'est spécifiquement Michelin et pas n'importe quelle app vélo ? »** → L'algorithme de pression (cœur du savoir-faire pneu), le réseau de revendeurs physiques, et la boucle achat-fidélisation. Aucun pure-player ne peut répliquer le réseau physique.
- **« Combien de temps pour passer en production réelle ? »** → Le socle est déjà déployé et CI/CD-isé. Il reste l'intégration matérielle et le durcissement sécurité/RGPD — pas une refonte.
- **« Comment vous mesurez le succès ? »** → Rétention (sorties/semaine), taux de conversion alerte → revendeur, progression dans les rangs. Tout est déjà trackable dans l'app.
