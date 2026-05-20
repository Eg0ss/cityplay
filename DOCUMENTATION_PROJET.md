# DOCUMENTATION COMPLÈTE DU PROJET CITYPLAY

Ce document retrace tout le parcours de développement du projet **Cityplay**, une plateforme d'aventure urbaine et de chasses au trésor numérique au Bénin. L'objectif est de permettre à n'importe quel développeur ou curieux de comprendre **quoi**, **où**, **comment** et **pourquoi** chaque élément a été construit.

---

## 1. Stack Technique (L'arsenal technologique)

Pour ce projet, nous avons choisi des outils modernes garantissant rapidité, sécurité et une expérience utilisateur fluide.

- **PHP 8.3 + Laravel 11** : Le socle de l'application. Laravel gère la base de données, la sécurité (authentification) et la logique métier.
- **Vue.js 3 (Composition API)** : Pour créer une interface réactive. Contrairement aux sites classiques, les pages ne se rechargent pas entièrement à chaque clic.
- **Inertia.js** : Le "pont" magique. Il permet de relier Laravel et Vue.js sans avoir besoin de créer une API complexe (REST ou GraphQL).
- **Tailwind CSS** : Pour le design. Il permet de créer des interfaces "Gaming" (mode sombre, couleurs vives) rapidement.
- **PrimeVue** : Une bibliothèque de composants prêts à l'emploi (boutons, formulaires, notifications) pour gagner du temps.
- **Leaflet.js** : Pour afficher les cartes interactives (OpenStreetMap) sans payer les frais de Google Maps.

---

## 2. Historique de Développement Étape par Étape

### Étape 1 : Initialisation et Fondations
**Le Rôle :** Mettre en place la structure de base et la sécurité.
**Le Pourquoi :** Un projet a besoin d'un cadre solide avant d'ajouter des fonctionnalités. L'authentification est la première étape pour savoir qui est qui.
- **Actions :** Installation de Laravel, configuration d'Inertia et de Laravel Breeze (pour la gestion des comptes utilisateurs).
- **Fichiers clés :**
    - [web.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/routes/web.php) : Définition des premières routes.
    - [app.blade.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/resources/views/app.blade.php) : Le fichier HTML principal qui charge Vue.js.
- **Logique :** Mise en place du système de rôles (Admin vs Joueur) via une colonne `is_admin` dans la table `users`.

### Étape 2 : Architecture de la Base de Données
**Le Rôle :** Définir comment les données sont stockées.
**Le Pourquoi :** Pour qu'un jeu fonctionne, il faut que les Villes contiennent des Lieux, et que ces Lieux proposent des Énigmes.
- **Fichiers (Migrations) :**
    - [create_cities_table.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/database/migrations/2026_05_15_154520_create_cities_table.php) : Stocke les villes (Cotonou, Ouidah, etc.).
    - [create_places_table.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/database/migrations/2026_05_13_191207_create_places_table.php) : Stocke les points d'intérêt (Monuments, Parcs).
    - [create_riddles_table.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/database/migrations/2026_05_13_191209_create_riddles_table.php) : Stocke les questions, réponses et indices.
- **Modèles :** [Place.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/app/Models/Place.php) et [Riddle.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/app/Models/Riddle.php) gèrent les relations (un lieu a plusieurs énigmes).

### Étape 3 : Le Dashboard Administrateur (Le Centre de Commande)
**Le Rôle :** Créer une interface pour gérer le contenu du jeu.
**Le Pourquoi :** L'admin doit pouvoir ajouter des lieux et des énigmes sans toucher au code ou à la base de données directement.
- **Fichiers :**
    - [AdminController.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/app/Http/Controllers/AdminController.php) : Gère les créations/modifications/suppressions.
    - [AdminLayout.vue](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/resources/js/Pages/Admin/AdminLayout.vue) : Design de la barre latérale et du menu.
- **Logique mise en place :** Sécurisation via le [AdminMiddleware.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/app/Http/Middleware/AdminMiddleware.php). Seuls les administrateurs peuvent accéder aux routes `/admin`.

### Étape 4 : Cartographie et Géo-localisation
**Le Rôle :** Intégrer des cartes pour placer les lieux.
**Le Pourquoi :** Cityplay est un jeu basé sur l'exploration réelle. Il faut pouvoir voir où se trouvent les défis.
- **Bibliothèques :** **Leaflet** pour l'affichage, **Nominatim API** pour transformer une adresse en coordonnées GPS.
- **Fichiers :** [AllPlaces.vue](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/resources/js/Pages/Admin/AllPlaces.vue).
- **Logique :** Lorsque l'admin tape le nom d'un lieu, le système interroge OpenStreetMap pour remplir automatiquement la latitude et la longitude.

### Étape 5 : Le Système d'Énigmes par Étapes
**Le Rôle :** Créer des formulaires de création d'énigmes complexes.
**Le Pourquoi :** Une énigme n'est pas juste une question. C'est un niveau de difficulté, une réponse, des indices et des images.
- **Fichiers :** [Enigmas.vue](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/resources/js/Pages/Admin/Enigmas.vue).
- **Logique :** Formulaire découpé en étapes (Stepper). Nettoyage des données (suppression des options vides dans les QCM) avant l'envoi au serveur.

### Étape 6 : Le Moteur de Jeu (Le Gameplay)
**Le Rôle :** Permettre aux joueurs de lancer une partie et de répondre aux questions.
**Le Pourquoi :** C'est le cœur de l'application pour l'utilisateur final.
- **Fichiers :**
    - [GameController.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/app/Http/Controllers/GameController.php) : Gère les sessions de jeu.
    - [GameSession.php](file:///c:/Users/marcel.yessia/Documents/LARAVEL/cityplay/app/Models/GameSession.php) : Suit la progression d'un joueur dans une partie.
- **Logique :** Création d'un token unique pour chaque partie, calcul des points (100 points par bonne réponse) et gestion des statuts (en attente, en cours, terminé).

---

## 3. Structure des Dossiers

| Dossier | Contenu |
| :--- | :--- |
| `app/Models/` | Définition des objets (Utilisateur, Lieu, Énigme, Session). |
| `app/Http/Controllers/` | Le "cerveau" PHP qui traite les actions. |
| `resources/js/Pages/` | Toutes les pages de l'application (Vue.js). |
| `resources/js/Components/` | Petits morceaux réutilisables (Boutons, Modales). |
| `database/migrations/` | Le plan de construction de la base de données. |
| `routes/web.php` | Le plan de circulation (URLs) du site. |

---

## 4. Bibliothèques et Dépendances

### Backend (PHP/Laravel)
- `laravel/breeze` : Système d'authentification prêt à l'emploi.
- `inertiajs/inertia-laravel` : Connexion avec Vue.js.

### Frontend (JS/Vue)
- `primevue` : Pour les composants d'interface élégants.
- `lucide-vue-next` : Pour les icônes modernes (radar, épée, carte).
- `axios` : Pour envoyer des données au serveur sans recharger la page.
- `leaflet` : Pour les cartes interactives.

---

## 5. Logiques Particulières

1.  **Mode Sombre (Dark Mode)** : L'application est forcée en mode sombre pour une esthétique "Gaming/Aventure".
2.  **Validation Automatique** : Les réponses aux énigmes sont comparées sans tenir compte des majuscules/minuscules pour être plus souple avec les joueurs.
3.  **Nettoyage des QCM** : Lors de la création d'une énigme, si l'admin laisse des cases de choix vides, le code les retire automatiquement avant de sauvegarder.

---
*Document mis à jour le 20 mai 2026 par l'assistant IA pour Cityplay.*

---

## Rapport synthétique technique (version courte)

Objectif: fournir une vue d'ensemble claire pour un développeur souhaitant comprendre le projet, son architecture et où trouver chaque fonctionnalité.

- Application: jeu d'exploration/énigmes géolocalisées (Cityplay).
- Backend: Laravel (Eloquent, migrations, events, broadcast).
- Frontend: Inertia + Vue.js (pages et composants dans `resources/js`).

---

## 1. Fichiers et points d'entrée importants

- Routes principales: [routes/web.php](routes/web.php)
- Layout serveur / Inertia: [resources/views/app.blade.php](resources/views/app.blade.php)
- Point d'entrée JS: [resources/js/app.js](resources/js/app.js) et [resources/js/bootstrap.js](resources/js/bootstrap.js)
- Pages Inertia principales: [resources/js/Pages](resources/js/Pages)

---

## 2. Base de données (migrations clés)

Les migrations décrivent le modèle de données central:

- `users`: comptes joueurs/admins — [database/migrations/0001_01_01_000000_create_users_table.php](database/migrations/0001_01_01_000000_create_users_table.php)
- `cities`: villes couvertes — [database/migrations/2026_05_15_154520_create_cities_table.php](database/migrations/2026_05_15_154520_create_cities_table.php)
- `places`: lieux géolocalisés (lat/lng, marges) — [database/migrations/2026_05_13_191207_create_places_table.php](database/migrations/2026_05_13_191207_create_places_table.php)
- `riddles`: énigmes (niveau, description, reponse, mcq_options) — [database/migrations/2026_05_13_191209_create_riddles_table.php](database/migrations/2026_05_13_191209_create_riddles_table.php)
- `riddle_images`, `hints` — images et indices associés aux énigmes
- `game_sessions`, `game_players`, `game_riddles`, `game_player_riddle_attempts`, `scores` — tables de gameplay/score

Remarque: des migrations additionnelles existent (modifications de colonnes, index, champs optionnels). Voir le dossier [database/migrations](database/migrations) pour l'historique complet.

---

## 3. Modèles Eloquent (résumé)

- `User` — relations: `gameSessions()` (via `game_players`), `scores()`, `gamePlayers()` — [app/Models/User.php](app/Models/User.php)
- `City` — `places()` — [app/Models/City.php](app/Models/City.php)
- `Place` — champs gps, `riddles()` — [app/Models/Place.php](app/Models/Place.php)
- `Riddle` — `images()`, `hints()`, `gameRiddles()` — [app/Models/Riddle.php](app/Models/Riddle.php)
- `GameSession` — `players()`, `gameRiddles()`, `attempts()`, `scores()` — [app/Models/GameSession.php](app/Models/GameSession.php)
- `GamePlayer`, `GameRiddle`, `GamePlayerRiddleAttempt`, `Score` — modèles opérationnels pour gérer inscriptions, tentatives et points.

Ces modèles implémentent la logique relationnelle principale entre sessions, joueurs, lieux et énigmes.

---

## 4. Contrôleurs et logique métier

- `GameEngineController` — logique métier du gameplay: création de session, sélection d'énigmes par proximité/niveau (Haversine), lobby multijoueur, enregistrement continu des résultats, dashboard joueur et progression. Utilise les événements `GameUpdated` et `LobbyUpdated`. Voir [app/Http/Controllers/GameEngineController.php](app/Http/Controllers/GameEngineController.php)
- `GameController` — actions liées aux sessions simples et soumission de réponses. Voir [app/Http/Controllers/GameController.php](app/Http/Controllers/GameController.php)
- `AdminController` — CRUD villes, lieux, énigmes; upload d'images; nettoyage des options QCM; gestion des indices. Voir [app/Http/Controllers/AdminController.php](app/Http/Controllers/AdminController.php)
- `PageController` — pages publiques (Inertia) : HowToPlay, Explore, Leaderboard, etc. Voir [app/Http/Controllers/PageController.php](app/Http/Controllers/PageController.php)

Sécurité: routes `game.*` protégées par `auth, verified`; routes `admin.*` protégées par middleware `admin`.

---

## 5. Frontend (Inertia + Vue)

- Pages clés: `resources/js/Pages/Game/Setup/Index.vue`, `Game/Play/Lobby.vue`, `Game/Play/ActiveRiddle.vue`, `Game/Dashboard.vue`, `Game/Progression.vue`.
- Composants UI réutilisables dans `resources/js/Components` (boutons, modales, formulaires).
- Auth: pages dans `resources/js/Pages/Auth`.
- Entrée: [resources/js/app.js](resources/js/app.js) initialise Inertia et monteur Vue.

Remarque: le rendu côté client utilise Inertia pour naviguer sans rechargement complet; la logique temps réel est gérée via events/broadcasting côté serveur et via listeners dans le frontend (bootstrap.js / store.js).

---

## 6. Points techniques et recommandations

- Événements temps réel: confirmer la configuration de `broadcasting.php` et les credentials Pusher/Redis pour la prod.
- Géo‑calculs: la sélection d'énigmes utilise une formule de distance (Haversine) en SQL; envisager l'ajout d'index ou d'une couche géo spécifique si la table `places` devient grande.
- Stockage des images: usage du disque `public` via `Storage::disk('public')`; ne pas oublier `php artisan storage:link` en déploiement.
- Validation & sécurité: vérifier le middleware `admin` et les règles `authorize` côté contrôleur si sensibles.

---

## 7. Où commencer si vous reprisez le projet

1. Installer dépendances et builder assets:

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan storage:link
```

2. Créer un compte admin (via Seeder ou DB) pour accéder à `/admin`.
3. Tester une partie: créer une `City` → ajouter `Place`(s) avec coordonnées → ajouter quelques `Riddle` → lancer `game/setup` et créer une session.

---

## 8. Souhaitez-vous que je :

- Génère un fichier `REPORT_DEVELOPER.md` plus détaillé (diagramme ER + explication des tables), ou
- Analyse une partie spécifique (sécurité, performance, tests), ou
- Liste et documente chaque endpoint API et payload attendu pour le frontend.

Répondez simplement par l'option souhaitée et je m'en occupe.

