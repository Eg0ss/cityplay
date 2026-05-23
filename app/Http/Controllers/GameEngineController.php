<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\GameSession;
use App\Models\GamePlayer;
use App\Models\GameRiddle;
use App\Models\Riddle;
use App\Models\GamePlayerRiddleAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GameEngineController extends Controller
{
    // Affiche le Dashboard Gaming avec les vraies statistiques en continu
    public function dashboard()
    {
        $userId = auth()->id();

        // 1. Nombre de parties jouées (nombre d'inscriptions à des sessions)
        $totalGames = GamePlayer::where('user_id', $userId)->count();

        // 2. Nombre d'énigmes résolues
        $riddlesSolved = GamePlayerRiddleAttempt::where('user_id', $userId)
            ->where('status', 'gagne')
            ->count();

        // 3. Total des points cumulés dans la table des scores
        $totalPoints = \App\Models\Score::where('user_id', $userId)->sum('points');

        return Inertia::render('Game/Dashboard', [
            'stats' => [
                'total_games' => $totalGames,
                'riddles_solved' => $riddlesSolved,
                'total_points' => $totalPoints,
            ]
        ]);
    }

    // Affiche le profil de progression détaillé du joueur
    public function progression()
    {
        $userId = auth()->id();

        // 1. Nombre total de sessions jouées
        $totalGames = GamePlayer::where('user_id', $userId)->count();

        // 2. Répartition des tentatives
        $attempts = GamePlayerRiddleAttempt::where('user_id', $userId)->get();
        $solvedCount = $attempts->where('status', 'gagne')->count();
        $failedCount = $attempts->where('status', 'perdu')->count();

        // 3. Score global cumulé (XP)
        $totalPoints = \App\Models\Score::where('user_id', $userId)->sum('points');

        // 4. Historique détaillé des tentatives récentes
        $recentAttempts = GamePlayerRiddleAttempt::with(['gameRiddle.riddle.place', 'session'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'status' => $attempt->status,
                    'points_earned' => $attempt->points_earned,
                    'time_spent' => $attempt->time_limit,
                    'riddle_title' => $attempt->gameRiddle->riddle->question ?? 'Énigme sans titre',
                    'place_name' => $attempt->gameRiddle->riddle->place->nom ?? 'Lieu inconnu',
                    'date' => $attempt->created_at->format('d/m/Y H:i'),
                ];
            });

        // 5. Calcul des paliers d'XP et niveaux de grade
        $levelName = "Aspirant";
        $nextLevelName = "Explorateur 🦁";
        $xpMin = 0;
        $xpMax = 200;

        if ($totalPoints >= 1000) {
            $levelName = "Légende du Bénin 👑";
            $nextLevelName = "Niveau Maximum";
            $xpMin = 1000;
            $xpMax = 1000;
        } elseif ($totalPoints >= 500) {
            $levelName = "Guide Aventure 🧙‍♂️";
            $nextLevelName = "Légende du Bénin 👑";
            $xpMin = 500;
            $xpMax = 1000;
        } elseif ($totalPoints >= 200) {
            $levelName = "Explorateur 🦁";
            $nextLevelName = "Guide Aventure 🧙‍♂️";
            $xpMin = 200;
            $xpMax = 500;
        }

        $progressPercent = $xpMax > $xpMin ? min(100, round((($totalPoints - $xpMin) / ($xpMax - $xpMin)) * 100)) : 100;

        // 6. Succès & Badges
        $badges = [
            [
                'id' => 'first_step',
                'title' => 'Premier Pas 🗺️',
                'description' => 'A terminé sa première session de jeu.',
                'unlocked' => $totalGames >= 1,
            ],
            [
                'id' => 'riddle_hunter',
                'title' => 'Chasseur d\'Énigmes 🕵️‍♂️',
                'description' => 'A résolu au moins 5 énigmes.',
                'unlocked' => $solvedCount >= 5,
            ],
            [
                'id' => 'xp_enthusiast',
                'title' => 'Passionné d\'XP ⚡',
                'description' => 'A franchi le cap des 500 points cumulés.',
                'unlocked' => $totalPoints >= 500,
            ],
            [
                'id' => 'benin_legend',
                'title' => 'Légende Locale 👑',
                'description' => 'Devenu une véritable légende du Bénin avec 1000+ XP.',
                'unlocked' => $totalPoints >= 1000,
            ],
        ];

        return Inertia::render('Game/Progression', [
            'levelName' => $levelName,
            'nextLevelName' => $nextLevelName,
            'totalPoints' => (int) $totalPoints,
            'xpMin' => $xpMin,
            'xpMax' => $xpMax,
            'progressPercent' => $progressPercent,
            'stats' => [
                'total_games' => $totalGames,
                'solved_count' => $solvedCount,
                'failed_count' => $failedCount,
            ],
            'recentAttempts' => $recentAttempts,
            'badges' => $badges,
        ]);
    }

    // Affiche le processus de configuration de partie avec la liste des villes
    public function setup()
    {
        $cities = \App\Models\City::where('status', 'active')->orderBy('name', 'asc')->get()->map(function ($city) {
            $placeIds = $city->places->pluck('id');
            
            // Compter le nombre total d'énigmes
            $riddlesCount = \App\Models\Riddle::whereIn('place_id', $placeIds)->count();
            
            // Compter le nombre d'énigmes par niveau
            $riddlesByLevel = \App\Models\Riddle::whereIn('place_id', $placeIds)
                ->selectRaw('niveau, COUNT(*) as count')
                ->groupBy('niveau')
                ->pluck('count', 'niveau');
            
            return [
                'id' => $city->id,
                'name' => $city->name,
                'departement' => $city->departement,
                'riddles_count' => $riddlesCount,
                'riddles_by_level' => [
                    'facile' => $riddlesByLevel->get(1, 0),
                    'intermediaire' => $riddlesByLevel->get(2, 0),
                    'difficile' => $riddlesByLevel->get(3, 0),
                ],
            ];
        });

        return Inertia::render('Game/Setup/Index', [
            'cities' => $cities
        ]);
    }

    // Gère la création de la session de jeu
    public function createSession(Request $request)
    {
        // Validation des paramètres de configuration envoyés par le client (Admin ou Joueur)
        $validated = $request->validate([
            'level' => 'required|string|in:facile,intermediaire,difficile',
            'location_type' => 'required|string|in:departement,commune,city,place',
            'location_id' => 'required|integer',
            'riddles_count' => 'required|integer|min:1',
            'type' => 'required|string|in:solo,participants,challengers',
            'challenger_mode' => 'nullable|string|in:reponse_par_membre,reponse_par_tous',
            'max_joueurs' => 'required|integer|min:1',
            'global_mode' => 'nullable|string|in:decouverte,gaming,mixte',
            'user_lat' => 'required|numeric',
            'user_lng' => 'required|numeric',
            'participate' => 'boolean', // Nouveau : Indique si le créateur (Admin) participe à la partie
        ]);

        $levelMapping = [
            'facile' => 1,
            'intermediaire' => 2,
            'difficile' => 3
        ];
        $niveauInt = $levelMapping[$validated['level']];

        // 1. Trouver les lieux les plus proches selon le type de localisation choisi (Ville ou Lieu précis)
        $lat = $validated['user_lat'];
        $lng = $validated['user_lng'];

        $query = \App\Models\Place::select('places.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            );

        if ($validated['location_type'] === 'place') {
            // Si l'admin a choisi un lieu spécifique, on se restreint à celui-ci
            $query->where('id', $validated['location_id']);
        } else {
            // Sinon on prend tous les lieux de la cité
            $query->where('city_id', $validated['location_id']);
        }

        $closestPlaces = $query->whereHas('riddles', function($query) use ($niveauInt) {
                $query->where('niveau', $niveauInt);
            })
            ->orderBy('distance', 'asc')
            ->get();

        // Vérification de la disponibilité des énigmes pour le niveau choisi
        if ($closestPlaces->isEmpty()) {
            return redirect()->back()->with('error', "Il n'y a pas d'énigme disponible pour le niveau " . $validated['level'] . " dans cette zone. 🏛️");
        }

        // Création de la session de jeu avec un Token unique pour le partage
        $token = Str::random(10);
        $session = GameSession::create([
            'statut' => 'en_attente',
            'lien_token' => $token,
            'max_joueurs' => $validated['type'] === 'solo' ? 1 : $validated['max_joueurs'],
            'level' => $validated['level'],
            'location_type' => $validated['location_type'],
            'location_id' => $validated['location_id'],
            'riddles_count' => $validated['riddles_count'],
            'type' => $validated['type'],
            'challenger_mode' => $validated['challenger_mode'],
        ]);

        // LOGIQUE DE PARTICIPATION : 
        // On n'ajoute le créateur comme joueur QUE s'il a choisi de participer (participate = true)
        // Par défaut, pour un joueur standard, c'est toujours vrai. Pour un Admin, c'est optionnel.
        if (!isset($validated['participate']) || $validated['participate']) {
            GamePlayer::create([
                'session_id' => $session->id,
                'user_id' => auth()->id(),
                'statut' => 'pret',
                'global_mode' => $validated['global_mode'] ?: 'mixte'
            ]);
        }

        // 2. Sélection et association des énigmes à la session de jeu
        $usedRiddleIds = collect();
        $riddlesCreated = 0;
        $targetCount = $validated['riddles_count'];
        
        $placeIndex = 0;
        while ($riddlesCreated < $targetCount && $placeIndex < $closestPlaces->count()) {
            $place = $closestPlaces[$placeIndex];
            
            // On pioche des énigmes aléatoirement dans les lieux trouvés
            $riddle = Riddle::where('place_id', $place->id)
                ->where('niveau', $niveauInt)
                ->whereNotIn('id', $usedRiddleIds)
                ->inRandomOrder()
                ->first();
            
            if ($riddle) {
                $usedRiddleIds->push($riddle->id);
                GameRiddle::create([
                    'session_id' => $session->id,
                    'riddle_id' => $riddle->id,
                ]);
                $riddlesCreated++;
            }
            
            $placeIndex++;
            if ($placeIndex >= $closestPlaces->count() && $riddlesCreated < $targetCount) {
                $placeIndex = 0;
                $remainingRiddles = Riddle::whereIn('place_id', $closestPlaces->pluck('id'))
                    ->where('niveau', $niveauInt)
                    ->whereNotIn('id', $usedRiddleIds)
                    ->exists();
                if (!$remainingRiddles) break;
            }
        }
        
        // Mise à jour du compteur final si moins d'énigmes trouvées que prévu
        if ($riddlesCreated < $targetCount) {
            $session->update(['riddles_count' => $riddlesCreated]);
        }

        // Redirection vers le Lobby où le lien de partage sera affiché
        return redirect()->route('game.lobby', ['token' => $token]);
    }

    // Enregistre les points et les tentatives du joueur en continu
    public function recordResult(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer',
            'riddle_id' => 'required|integer',
            'status' => 'required|string|in:gagne,perdu',
            'points' => 'required|integer',
            'mode_choisi' => 'required|string',
            'temps_resolution' => 'nullable|integer'
        ]);

        $gameRiddle = GameRiddle::where('session_id', $validated['session_id'])
            ->where('riddle_id', $validated['riddle_id'])
            ->first();
        
        if (!$gameRiddle) {
            $gameRiddle = GameRiddle::create([
                'session_id' => $validated['session_id'],
                'riddle_id' => $validated['riddle_id'],
            ]);
        }

        // Vérification de verrouillage en mode 'participants'
        $session = GameSession::findOrFail($validated['session_id']);

        if ($session->statut === 'termine') {
            return response()->json([
                'success' => false,
                'session_finished' => true,
                'message' => 'Cette session est déjà terminée.',
            ]);
        }

        if ($session->type === 'participants') {
            $alreadyAttempted = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)
                ->exists();
                
            if ($alreadyAttempted) {
                return response()->json([
                    'success' => false,
                    'already_solved' => true,
                    'message' => "Désolé ! Un autre participant de la session a déjà clôturé cette énigme !"
                ]);
            }
        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_membre') {
            $alreadyAttempted = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)
                ->exists();
                
            if ($alreadyAttempted) {
                return response()->json([
                    'success' => false,
                    'already_solved' => true,
                    'message' => "Trop tard ! Un challenger a déjà répondu à cette énigme."
                ]);
            }
        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_tous') {
            $alreadyAttemptedByUser = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)
                ->where('user_id', auth()->id())
                ->exists();
                
            if ($alreadyAttemptedByUser) {
                return response()->json([
                    'success' => false,
                    'already_solved' => true,
                    'message' => "Vous avez déjà répondu à cette énigme."
                ]);
            }
        }

        // En mode participants ou challengers (reponse_par_membre): chaque joueur ne gagne des points que s'il résout l'énigme
        $pointsToAward = 0;
        if ($validated['status'] === 'gagne') {
            $pointsToAward = $validated['points'];
        }

        GamePlayerRiddleAttempt::create([
            'game_session_id' => $validated['session_id'],
            'user_id' => auth()->id(),
            'game_riddle_id' => $gameRiddle->id,
            'mode_choisi' => $validated['mode_choisi'],
            'status' => $validated['status'],
            'points_earned' => $pointsToAward,
            'time_limit' => $validated['temps_resolution'] ?: 0,
            'started_at' => now(),
        ]);

        // Créer une entrée Score UNIQUEMENT si le joueur a gagné
        if ($pointsToAward > 0) {
            \App\Models\Score::create([
                'session_id' => $validated['session_id'],
                'user_id' => auth()->id(),
                'points' => $pointsToAward,
                'temps_resolution' => $validated['temps_resolution'] ?: 0
            ]);
        }

        $sessionFinished = false;
        
        // Logique de fin de session selon le type
        if ($session->type === 'solo') {
            // En mode solo : vérifier si le joueur a répondu au nombre d'énigmes configuré
            $userAttempts = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('user_id', auth()->id())
                ->count();
            
            $targetCount = (int) $session->riddles_count;
            
            if ($userAttempts >= $targetCount) {
                $session->update(['statut' => 'termine']);
                $sessionFinished = true;
            }
        } elseif ($session->type === 'participants' || ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_membre')) {
            // En mode participants ou challenger (membre): vérifier si toutes les énigmes ont été résolues par l'équipe/groupe
            $sessionRiddlesInPlay = GameRiddle::where('session_id', $session->id)->count();
            $targetAnswered = min(max((int) $session->riddles_count, 0), $sessionRiddlesInPlay);

            if ($targetAnswered > 0) {
                $distinctAnsweredRow = DB::table('game_player_riddle_attempts')
                    ->where('game_session_id', $session->id)
                    ->selectRaw('count(distinct game_riddle_id) as cnt')
                    ->first();
                $distinctAnswered = (int) ($distinctAnsweredRow->cnt ?? 0);

                if ($distinctAnswered >= $targetAnswered) {
                    $session->update(['statut' => 'termine']);
                    $sessionFinished = true;
                }
            }
        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_tous') {
            // En mode challenger (tous): vérifier si TOUS les joueurs ont répondu à TOUTES les énigmes
            $playersCount = $session->players()->count();
            $sessionRiddlesCount = GameRiddle::where('session_id', $session->id)->count();
            $totalAttemptsRequired = $playersCount * $sessionRiddlesCount;
            
            $totalAttempts = GamePlayerRiddleAttempt::where('game_session_id', $session->id)->count();
            
            if ($totalAttempts >= $totalAttemptsRequired) {
                $session->update(['statut' => 'termine']);
                $sessionFinished = true;
            }
        }

        // Déclencher l'événement de mise à jour du jeu en temps réel !
        $session->refresh();
        event(new \App\Events\GameUpdated($session));

        return response()->json([
            'success' => true,
            'session_finished' => $sessionFinished,
        ]);
    }

    // Affiche le Lobby (salle d'attente Multijoueur) ou lance directement si Solo
    public function lobby($token)
    {
        $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();
        
        // Si c'est une partie Solo et que l'utilisateur actuel est le joueur, on lance
        if ($session->type === 'solo' && $session->players->firstWhere('user_id', auth()->id())) {
            $session->update(['statut' => 'en_cours']);
            return redirect()->route('game.play', ['token' => $token]);
        }

        // Vérifier si l'utilisateur actuel est déjà inscrit dans cette session
        $currentPlayer = $session->players->firstWhere('user_id', auth()->id());
        
        // LOGIQUE D'AUTO-AJOUT :
        // On n'ajoute pas l'utilisateur s'il est déjà joueur, ou s'il est Admin (pour lui permettre d'être spectateur/générateur de lien)
        if (!$currentPlayer && !auth()->user()->is_admin) {
            // Si la session n'est pas pleine, ajouter le joueur automatiquement
            if ($session->players->count() < $session->max_joueurs) {
                $creatorPlayer = $session->players->first();
                $globalMode = $creatorPlayer ? $creatorPlayer->global_mode : 'mixte';

                GamePlayer::create([
                    'session_id' => $session->id,
                    'user_id' => auth()->id(),
                    'statut' => 'pret',
                    'global_mode' => $globalMode
                ]);
                
                // Recharger la session avec le nouveau joueur
                $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();

                // Déclencher l'événement de mise à jour du lobby
                event(new \App\Events\LobbyUpdated($session));
            } else if ($session->type !== 'solo') {
                // Session pleine (hors solo), rediriger vers le dashboard
                return redirect()->route('game.dashboard')->with('error', 'Désolé, cette session de jeu est déjà complète.');
            }
        }

        return Inertia::render('Game/Play/Lobby', [
            'session' => $session
        ]);
    }

    // Démarre la session multijoueur (Host uniquement)
    public function startGame($token)
    {
        $session = GameSession::where('lien_token', $token)->firstOrFail();
        $session->update(['statut' => 'en_cours']);
        
        // Déclencher l'événement de démarrage de partie pour rediriger les participants !
        event(new \App\Events\LobbyUpdated($session));

        return redirect()->route('game.play', ['token' => $token]);
    }

    // Affiche l'interface de jeu dynamique
    public function play($token)
    {
        $session = GameSession::with(['gameRiddles.riddle.place.riddles', 'gameRiddles.riddle.images', 'gameRiddles.riddle.hints', 'players.user', 'attempts.gameRiddle', 'attempts.user'])
            ->where('lien_token', $token)
            ->firstOrFail();

        if ($session->statut === 'termine') {
            return redirect()->route('game.dashboard')
                ->with('success', 'Cette session de jeu est terminée. Bravo à l\'équipe !');
        }

        // Construire une structure de quêtes (Steps) avec l'énigme et son lieu associé
        $gameSteps = [];
        
        // Charger les relations nécessaires pour éviter les requêtes N+1 et permettre l'accès instantané aux indices
        $session->load(['gameRiddles.riddle.hints', 'gameRiddles.riddle.images', 'gameRiddles.riddle.place']);
        
        foreach ($session->gameRiddles as $gameRiddle) {
            $riddle = $gameRiddle->riddle;
            $place = $riddle->place;
            
            $gameSteps[] = [
                'id' => $place->id,
                'nom' => $place->nom,
                'latitude' => $place->lat,
                'longitude' => $place->lng,
                'rayon_marge' => $place->rayon_marge,
                'image' => $place->image,
                'verified_description' => $place->verified_description,
                'riddle' => $riddle // Contient maintenant hints et images
            ];
        }

        return Inertia::render('Game/Play/ActiveRiddle', [
            'session' => $session,
            'gameSteps' => $gameSteps
        ]);
    }

    // Récupère les indices pour une énigme donnée
    public function getHints($riddleId)
    {
        $riddle = Riddle::findOrFail($riddleId);
        
        $hints = $riddle->hints()
            ->select('id', 'type', 'content', 'difficulty_level', 'order')
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'hints' => $hints,
        ]);
    }
}
