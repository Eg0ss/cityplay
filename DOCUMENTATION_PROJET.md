# DOCUMENTATION DU PROJET CITYPLAY

Ce document présente le fonctionnement, l'architecture et les fichiers importants du projet **Cityplay**.
Il a été mis à jour pour refléter l'organisation actuelle du code, les routes principales, les modèles, les contrôleurs et le frontend.

---

## 1. Vue d'ensemble

Cityplay est une application de chasse au trésor urbaine basée sur des lieux et des énigmes, avec :
- des pages publiques d'information,
- un moteur de jeu configuré par le joueur,
- un dashboard de progression,
- un panneau d'administration pour gérer villes, lieux et énigmes.

Stack technique:
- Backend : Laravel 11 (PHP 8.3)
- Frontend : Vue.js 3 + Inertia.js
- UI/CSS : Tailwind CSS
- Carte : Leaflet.js
- Auth : Laravel Breeze / Inertia

---

## 2. Structure principale du projet

### Fichiers principaux
- `routes/web.php` : définition des routes publiques, jeu et admin.
- `app/Http/Controllers/GameEngineController.php` : logique de création de jeu, lobby, play, score et progression.
- `app/Http/Controllers/AdminController.php` : gestion des villes, lieux et énigmes.
- `app/Http/Controllers/PageController.php` : pages statiques publiques (blog, contact, explore, etc.).
- `resources/views/app.blade.php` : layout principal Inertia.
- `resources/js/app.js` : point d'entrée JavaScript de l'application.
- `resources/js/Pages` : pages Vue/Inertia de l'application.

### Dossiers importants
- `app/Models/` : modèles Eloquent.
- `app/Http/Controllers/` : contrôleurs Laravel.
- `database/migrations/` : migrations de base de données.
- `resources/js/Pages/` : pages utilisateur et admin.
- `resources/js/Components/` : composants Vue réutilisables.

---

## 3. Base de données et tables principales

### Tables essentielles
- `users` : comptes des joueurs et administrateurs.
- `cities` : villes dans lesquelles se déroulent les jeux.
- `places` : lieux géolocalisés associés à une ville.
- `riddles` : énigmes, questions et réponses.
- `riddle_images` : images attachées aux énigmes.
- `hints` : indices liés aux énigmes.
- `game_sessions` : sessions de jeu créées par les joueurs.
- `game_players` : inscription des utilisateurs aux sessions.
- `game_riddles` : association des énigmes aux sessions.
- `game_player_riddle_attempts` : tentatives des joueurs sur chaque énigme.
- `scores` : points gagnés par les joueurs.

### Migrations clés
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_05_15_154520_create_cities_table.php`
- `database/migrations/2026_05_13_191207_create_places_table.php`
- `database/migrations/2026_05_13_191209_create_riddles_table.php`
- `database/migrations/2026_05_15_120736_create_game_sessions_table.php`
- `database/migrations/2026_05_15_120756_create_game_players_table.php`
- `database/migrations/2026_05_15_120758_create_game_riddles_table.php`
- `database/migrations/2026_05_18_091626_create_game_player_riddle_attempts_table.php`
- `database/migrations/2026_05_15_120800_create_scores_table.php`

---

## 4. Modèles Eloquent importants

### `User` (`app/Models/User.php`)
- Attributs : `name`, `email`, `password`, `role`.
- Relations :
  - `gameSessions()` : sessions auxquelles l'utilisateur est lié via `game_players`.
  - `scores()` : points gagnés.
  - `gamePlayers()` : inscriptions aux sessions.

### `City` (`app/Models/City.php`)
- Attributs : `name`, `description`, `slug`, `departement`.
- Relation : `places()`.

### `Place` (`app/Models/Place.php`)
- Attributs : `nom`, `image`, `ville`, `city_id`, `departement`, `lat`, `lng`, `rayon_marge`, `marge_validation_gps`, `is_active`, `verified_description`.
- Relation : `riddles()`.

### `Riddle` (`app/Models/Riddle.php`)
- Attributs : `place_id`, `niveau`, `description`, `reponse`, `mcq_options`, `indice_id`.
- Relations : `place()`, `images()`, `gameRiddles()`, `hints()`.
- `mcq_options` est casté en `array`.

### `GameSession` (`app/Models/GameSession.php`)
- Attributs : `statut`, `lien_token`, `max_joueurs`, `level`, `location_type`, `location_id`, `riddles_count`, `type`, `challenger_mode`.
- Relations : `players()`, `attempts()`, `users()`, `gameRiddles()`, `riddles()`, `scores()`.

### `GamePlayer` (`app/Models/GamePlayer.php`)
- Attributs : `session_id`, `user_id`, `mode_choisi`, `statut`, `global_mode`.
- Relations : `session()`, `user()`.

### `GameRiddle` (`app/Models/GameRiddle.php`)
- Attributs : `session_id`, `riddle_id`, `repondu_par`, `verrouille_a`.
- Relations : `session()`, `riddle()`, `solver()`.

### `GamePlayerRiddleAttempt` (`app/Models/GamePlayerRiddleAttempt.php`)
- Garde la trace des tentatives, du statut et des points.
- Relations : `session()`, `user()`, `gameRiddle()`.

### `Score` (`app/Models/Score.php`)
- Attributs : `session_id`, `user_id`, `points`, `temps_resolution`.
- Relations : `session()`, `user()`.

---

## 5. Routes et navigation principale

### Routes publiques
- `/` : page d'accueil Inertia.
- `/comment-jouer`, `/explorer`, `/classement`, `/blog`, `/a-propos`, `/contact` : pages d'information.
- `/lieux/{id}` : page de détail d'un lieu.

### Routes de jeu (`/game/...`)
- `/game/dashboard` : dashboard joueur.
- `/game/setup` : configuration d'une session.
- `/game/progression` : progression et historique.
- `/game/sessions` : création de session (POST).
- `/game/lobby/{token}` : salle d'attente.
- `/game/lobby/{token}/start` : démarrer la partie.
- `/game/play/{token}` : interface de jeu.
- `/game/play/record` : enregistrement des résultats (POST).

### Routes admin (`/admin/...`)
- Gestion villes : `/admin/cities`.
- Gestion lieux : `/admin/places` et `/admin/cities/{city}/places`.
- Gestion énigmes : `/admin/enigmas` et `/admin/places/{place}/enigmas`.
- Actions CRUD : création, mise à jour, suppression des villes, lieux et énigmes.

### Routes utilisateur
- `/profile` : édition de profil (authentifié).
- Auth : routes Laravel Breeze dans `routes/auth.php`.

### Sécurité
- `auth`, `verified` : protègent les routes de jeu.
- `admin` middleware : protège les routes admin.

---

## 6. Frontend Inertia & Vue

### Pages clés
- `resources/js/Pages/Welcome.vue`
- `resources/js/Pages/ShowPlace.vue`
- `resources/js/Pages/Explore.vue`
- `resources/js/Pages/Leaderboard.vue`
- `resources/js/Pages/Game/Dashboard.vue`
- `resources/js/Pages/Game/Setup/Index.vue`
- `resources/js/Pages/Game/Progression.vue`
- `resources/js/Pages/Game/Play/Lobby.vue`
- `resources/js/Pages/Game/Play/ActiveRiddle.vue`
- `resources/js/Pages/Admin/Dashboard.vue`
- `resources/js/Pages/Admin/Cities.vue`
- `resources/js/Pages/Admin/Places.vue`
- `resources/js/Pages/Admin/Enigmas.vue`
- `resources/js/Pages/Admin/AllPlaces.vue`
- `resources/js/Pages/Admin/AllEnigmas.vue`

### Composants importants
- `resources/js/Components/PrimaryButton.vue`
- `resources/js/Components/SecondaryButton.vue`
- `resources/js/Components/Modal.vue`
- `resources/js/Components/TextInput.vue`
- `resources/js/Components/Dropdown.vue`
- `resources/js/Components/ResponsiveNavLink.vue`

### Entrée JavaScript
- `resources/js/app.js`
- `resources/js/bootstrap.js`
- `resources/js/store.js`

---

## 7. Points techniques à connaître

- Les sessions de jeu sont créées avec un token unique (`lien_token`).
- La sélection d'énigmes dans `GameEngineController` utilise la distance géographique pour prioriser les lieux proches.
- Les points sont enregistrés dans `scores` seulement quand le joueur gagne.
- La progression montre les tentatives et les badges basés sur les points cumulés.
- Les indices sont récupérés via `GameEngineController::getHints()` et exposés en JSON.
- Les images de lieux et d'énigmes sont stockées dans le disque `public` (via `Storage::disk('public')`).

---

## 8. Installation et démarrage

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan storage:link
php artisan serve
```

Ensuite, connectez-vous avec un utilisateur admin ou créez un compte, puis testez : créer une ville, ajouter un lieu, ajouter une énigme, lancer une session de jeu.

---

## 9. À vérifier après mise à jour

- `config/broadcasting.php` si vous utilisez les événements en temps réel.
- `config/filesystems.php` pour le disque `public`.
- `routes/auth.php` pour l'authentification utilisateur.
- `app/Http/Middleware/AdminMiddleware.php` pour la protection admin.

---

*Document mis à jour le 21 mai 2026 par l'assistant IA.*

