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
        $nextLevelName = "Explorateur";
        $xpMin = 0;
        $xpMax = 200;

        if ($totalPoints >= 1000) {
            $levelName = "Légende du Bénin";
            $nextLevelName = "Niveau Maximum";
            $xpMin = 1000;
            $xpMax = 1000;
        } elseif ($totalPoints >= 500) {
            $levelName = "Guide Aventure";
            $nextLevelName = "Légende du Bénin";
            $xpMin = 500;
            $xpMax = 1000;
        } elseif ($totalPoints >= 200) {
            $levelName = "Explorateur";
            $nextLevelName = "Guide Aventure";
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
        $cities = \App\Models\City::orderBy('name', 'asc')->get()->map(function ($city) {
            $riddlesCount = \App\Models\Riddle::whereIn('place_id', $city->places->pluck('id'))->count();
            return [
                'id' => $city->id,
                'name' => $city->name,
                'departement' => $city->departement,
                'riddles_count' => $riddlesCount,
            ];
        });

        return Inertia::render('Game/Setup/Index', [
            'cities' => $cities
        ]);
    }

    // Gère la création de la session de jeu
    public function createSession(Request $request)
    {
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
        ]);

        $levelMapping = [
            'facile' => 1,
            'intermediaire' => 2,
            'difficile' => 3
        ];
        $niveauInt = $levelMapping[$validated['level']];

        // 1. Trouver toutes les énigmes disponibles dans la ville pour ce niveau
        $allRiddlesInCity = Riddle::whereHas('place', function($query) use ($validated) {
            $query->where('city_id', $validated['location_id']);
        })->where('niveau', $niveauInt)->get();

        // S'il n'y a pas d'énigmes pour cette ville et ce niveau
        if ($allRiddlesInCity->isEmpty()) {
            return redirect()->back()->with('error', "Il n'y a pas d'énigme disponible pour le niveau " . $validated['level'] . " dans cette ville. La mairie se hâtera d'y ajouter des énigmes palpitantes ! 🏛️");
        }

        // Ajuster le nombre d'énigmes demandées selon ce qui est disponible
        $requestedCount = $validated['riddles_count'];
        $availableCount = $allRiddlesInCity->count();
        $actualRiddlesCount = min($requestedCount, $availableCount);

        // 2. Trouver les lieux les plus proches qui APPARTIENNENT STRICTEMENT à la ville choisie et qui ONT des énigmes de ce niveau
        $lat = $validated['user_lat'];
        $lng = $validated['user_lng'];

        $closestPlaces = \App\Models\Place::select('places.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            )
            ->where('city_id', $validated['location_id'])
            ->whereHas('riddles', function($query) use ($niveauInt) {
                $query->where('niveau', $niveauInt);
            })
            ->orderBy('distance', 'asc')
            ->get();

        $token = Str::random(10);
        $session = GameSession::create([
            'statut' => 'en_attente',
            'lien_token' => $token,
            'max_joueurs' => $validated['type'] === 'solo' ? 1 : $validated['max_joueurs'],
            'level' => $validated['level'],
            'location_type' => $validated['location_type'],
            'location_id' => $validated['location_id'],
            'riddles_count' => $actualRiddlesCount,
            'type' => $validated['type'],
            'challenger_mode' => $validated['challenger_mode'],
        ]);

        // Ajouter le créateur comme joueur
        GamePlayer::create([
            'session_id' => $session->id,
            'user_id' => auth()->id(),
            'statut' => 'pret',
            'global_mode' => $validated['global_mode']
        ]);

        // 3. Sélectionner les énigmes en priorisant les lieux les plus proches
        $usedRiddleIds = collect();
        $riddlesSelected = 0;

        foreach ($closestPlaces as $place) {
            if ($riddlesSelected >= $actualRiddlesCount) break;

            // Prendre toutes les énigmes de ce lieu du bon niveau
            $placeRiddles = Riddle::where('place_id', $place->id)
                ->where('niveau', $niveauInt)
                ->whereNotIn('id', $usedRiddleIds)
                ->inRandomOrder()
                ->get();

            foreach ($placeRiddles as $riddle) {
                if ($riddlesSelected >= $actualRiddlesCount) break;

                $usedRiddleIds->push($riddle->id);
                GameRiddle::create([
                    'session_id' => $session->id,
                    'riddle_id' => $riddle->id,
                ]);
                $riddlesSelected++;
            }
        }

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
        }

        // En mode participants: chaque joueur ne gagne des points que s'il résout l'énigme
        // Les autres joueurs ne gagnent pas de points (pas de cumul)
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
        if ($session->type === 'participants') {
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

        if ($session->type === 'solo') {
            // Lancer la partie directement
            $session->update(['statut' => 'en_cours']);
            return redirect()->route('game.play', ['token' => $token]);
        }

        // Vérifier si l'utilisateur actuel est déjà inscrit dans cette session
        $currentPlayer = $session->players->firstWhere('user_id', auth()->id());

        if (!$currentPlayer) {
            // Si la session n'est pas pleine, ajouter le joueur
            if ($session->players->count() < $session->max_joueurs) {
                $creatorPlayer = $session->players->first();
                $globalMode = $creatorPlayer ? $creatorPlayer->global_mode : 'gaming';

                GamePlayer::create([
                    'session_id' => $session->id,
                    'user_id' => auth()->id(),
                    'statut' => 'pret',
                    'global_mode' => $globalMode
                ]);

                // Recharger la session avec le nouveau joueur
                $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();

                // Déclencher l'événement de mise à jour du lobby en temps réel !
                event(new \App\Events\LobbyUpdated($session));
            } else {
                // Session pleine, rediriger vers le dashboard avec un message
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
        $session = GameSession::with(['gameRiddles.riddle.place', 'players.user', 'attempts.gameRiddle', 'attempts.user'])
            ->where('lien_token', $token)
            ->firstOrFail();

        if ($session->statut === 'termine') {
            return redirect()->route('game.dashboard')
                ->with('success', 'Cette session de jeu est terminée. Bravo à l\'équipe !');
        }

        // Trouver les IDs des lieux de cette session dans l'ordre de distance initial
        $placeIds = $session->gameRiddles->pluck('riddle.place_id')->unique()->values();

        $levelMapping = [
            'facile' => 1,
            'intermediaire' => 2,
            'difficile' => 3
        ];
        $niveauInt = $levelMapping[$session->level];

        // Récupérer tous les lieux avec TOUTES leurs énigmes du niveau choisi
        $places = \App\Models\Place::with(['riddles' => function($query) use ($niveauInt) {
                $query->where('niveau', $niveauInt);
            }])
            ->whereIn('id', $placeIds)
            ->get();

        // Réordonner les lieux selon l'ordre initial calculé par proximité (les IDs de $placeIds)
        $placesSorted = $placeIds->map(function ($id) use ($places) {
            return $places->firstWhere('id', $id);
        })->filter()->values();

        return Inertia::render('Game/Play/ActiveRiddle', [
            'session' => $session,
            'placesWithRiddles' => $placesSorted
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
