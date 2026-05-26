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

        $totalGames = GamePlayer::where('user_id', $userId)->count();
        $riddlesSolved = GamePlayerRiddleAttempt::where('user_id', $userId)
            ->where('status', 'gagne')
            ->count();
        $totalPoints = \App\Models\Score::where('user_id', $userId)->sum('points');

        return Inertia::render('Game/Dashboard', [
            'stats' => [
                'total_games'    => $totalGames,
                'riddles_solved' => $riddlesSolved,
                'total_points'   => $totalPoints,
            ]
        ]);
    }

    // Affiche le profil de progression détaillé du joueur
    public function progression()
    {
        $userId = auth()->id();

        $totalGames = GamePlayer::where('user_id', $userId)->count();
        $attempts = GamePlayerRiddleAttempt::where('user_id', $userId)->get();
        $solvedCount = $attempts->where('status', 'gagne')->count();
        $failedCount  = $attempts->where('status', 'perdu')->count();
        $totalPoints  = \App\Models\Score::where('user_id', $userId)->sum('points');

        $recentAttempts = GamePlayerRiddleAttempt::with(['gameRiddle.riddle.place', 'session'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id'             => $attempt->id,
                    'status'         => $attempt->status,
                    'points_earned'  => $attempt->points_earned,
                    'time_spent'     => $attempt->time_limit,
                    'riddle_title'   => $attempt->gameRiddle->riddle->question ?? 'Énigme sans titre',
                    'place_name'     => $attempt->gameRiddle->riddle->place->nom ?? 'Lieu inconnu',
                    'date'           => $attempt->created_at->format('d/m/Y H:i'),
                ];
            });

        $levelName     = "Aspirant";
        $nextLevelName = "Explorateur 🦁";
        $xpMin = 0;
        $xpMax = 200;

        if ($totalPoints >= 1000) {
            $levelName     = "Légende du Bénin 👑";
            $nextLevelName = "Niveau Maximum";
            $xpMin = 1000;
            $xpMax = 1000;
        } elseif ($totalPoints >= 500) {
            $levelName     = "Guide Aventure 🧙‍♂️";
            $nextLevelName = "Légende du Bénin 👑";
            $xpMin = 500;
            $xpMax = 1000;
        } elseif ($totalPoints >= 200) {
            $levelName     = "Explorateur 🦁";
            $nextLevelName = "Guide Aventure 🧙‍♂️";
            $xpMin = 200;
            $xpMax = 500;
        }

        $progressPercent = $xpMax > $xpMin
            ? min(100, round((($totalPoints - $xpMin) / ($xpMax - $xpMin)) * 100))
            : 100;

        $badges = [
            ['id' => 'first_step',    'title' => 'Premier Pas 🗺️',           'description' => 'A terminé sa première session de jeu.',             'unlocked' => $totalGames >= 1],
            ['id' => 'riddle_hunter', 'title' => 'Chasseur d\'Énigmes 🕵️‍♂️', 'description' => 'A résolu au moins 5 énigmes.',                      'unlocked' => $solvedCount >= 5],
            ['id' => 'xp_enthusiast', 'title' => 'Passionné d\'XP ⚡',        'description' => 'A franchi le cap des 500 points cumulés.',           'unlocked' => $totalPoints >= 500],
            ['id' => 'benin_legend',  'title' => 'Légende Locale 👑',         'description' => 'Devenu une véritable légende du Bénin avec 1000+ XP.', 'unlocked' => $totalPoints >= 1000],
        ];

        return Inertia::render('Game/Progression', [
            'levelName'       => $levelName,
            'nextLevelName'   => $nextLevelName,
            'totalPoints'     => (int) $totalPoints,
            'xpMin'           => $xpMin,
            'xpMax'           => $xpMax,
            'progressPercent' => $progressPercent,
            'stats'           => [
                'total_games'   => $totalGames,
                'solved_count'  => $solvedCount,
                'failed_count'  => $failedCount,
            ],
            'recentAttempts' => $recentAttempts,
            'badges'         => $badges,
        ]);
    }

    // Affiche le processus de configuration de partie avec la liste des villes
    public function setup()
    {
        $cities = \App\Models\City::where('status', 'active')->orderBy('name', 'asc')->get()->map(function ($city) {
            $placeIds = $city->places->pluck('id');
            $riddlesCount = \App\Models\Riddle::whereIn('place_id', $placeIds)->count();
            $riddlesByLevel = \App\Models\Riddle::whereIn('place_id', $placeIds)
                ->selectRaw('niveau, COUNT(*) as count')
                ->groupBy('niveau')
                ->pluck('count', 'niveau');

            return [
                'id'            => $city->id,
                'name'          => $city->name,
                'departement'   => $city->departement,
                'riddles_count' => $riddlesCount,
                'riddles_by_level' => [
                    'facile'        => $riddlesByLevel->get(1, 0),
                    'intermediaire' => $riddlesByLevel->get(2, 0),
                    'difficile'     => $riddlesByLevel->get(3, 0),
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
        $validated = $request->validate([
            'level'           => 'required|string|in:facile,intermediaire,difficile',
            'location_type'   => 'required|string|in:departement,commune,city,place',
            'location_id'     => 'required|integer',
            'riddles_count'   => 'required|integer|min:1',
            'type'            => 'required|string|in:solo,participants,challengers',
            'challenger_mode' => 'nullable|string|in:reponse_par_membre,reponse_par_tous',
            'max_joueurs'     => 'required|integer|min:1',
            'global_mode'     => 'nullable|string|in:decouverte,gaming,mixte',
            'user_lat'        => 'required|numeric',
            'user_lng'        => 'required|numeric',
            'participate'     => 'boolean',
        ]);

        $levelMapping = ['facile' => 1, 'intermediaire' => 2, 'difficile' => 3];
        $niveauInt    = $levelMapping[$validated['level']];
        $lat = $validated['user_lat'];
        $lng = $validated['user_lng'];

        $query = \App\Models\Place::select('places.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            );

        if ($validated['location_type'] === 'place') {
            $query->where('id', $validated['location_id']);
        } else {
            $query->where('city_id', $validated['location_id']);
        }

        $closestPlaces = $query->whereHas('riddles', function ($query) use ($niveauInt) {
            $query->where('niveau', $niveauInt);
        })->orderBy('distance', 'asc')->get();

        if ($closestPlaces->isEmpty()) {
            return redirect()->back()->with('error', "Il n'y a pas d'énigme disponible pour le niveau " . $validated['level'] . " dans cette zone. 🏛️");
        }

        $token   = Str::random(10);
        $session = GameSession::create([
            'statut'          => 'en_attente',
            'lien_token'      => $token,
            'max_joueurs'     => $validated['type'] === 'solo' ? 1 : $validated['max_joueurs'],
            'level'           => $validated['level'],
            'location_type'   => $validated['location_type'],
            'location_id'     => $validated['location_id'],
            'riddles_count'   => $validated['riddles_count'],
            'type'            => $validated['type'],
            'challenger_mode' => $validated['challenger_mode'],
        ]);

        if (!isset($validated['participate']) || $validated['participate']) {
            GamePlayer::create([
                'session_id'  => $session->id,
                'user_id'     => auth()->id(),
                'statut'      => 'pret',
                'global_mode' => $validated['global_mode'] ?: 'mixte'
            ]);
        }

        $usedRiddleIds  = collect();
        $riddlesCreated = 0;
        $targetCount    = $validated['riddles_count'];
        $placeIndex     = 0;

        while ($riddlesCreated < $targetCount && $placeIndex < $closestPlaces->count()) {
            $place  = $closestPlaces[$placeIndex];
            $riddle = Riddle::where('place_id', $place->id)
                ->where('niveau', $niveauInt)
                ->whereNotIn('id', $usedRiddleIds)
                ->inRandomOrder()
                ->first();

            if ($riddle) {
                $usedRiddleIds->push($riddle->id);
                GameRiddle::create([
                    'session_id' => $session->id,
                    'riddle_id'  => $riddle->id,
                    'statut'     => 'ouvert',
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

        if ($riddlesCreated < $targetCount) {
            $session->update(['riddles_count' => $riddlesCreated]);
        }

        return redirect()->route('game.lobby', ['token' => $token]);
    }

    /**
     * ─────────────────────────────────────────────────────────────────
     * NOUVEAU : Verrouillage atomique d'une énigme AVANT la soumission.
     *
     * Appelé dès que le joueur clique "Valider" → bloque l'énigme pour
     * tous les autres joueurs en temps réel via RiddleLocked (WebSocket).
     * ─────────────────────────────────────────────────────────────────
     */
    public function lockRiddle(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer',
            'riddle_id'  => 'required|integer',
        ]);

        $session = GameSession::findOrFail($validated['session_id']);

        // En mode solo ou challengers (reponse_par_tous), pas de verrouillage exclusif
        $needsLock = in_array($session->type, ['participants', 'challengers'])
            && $session->challenger_mode !== 'reponse_par_tous';

        $gameRiddle = GameRiddle::where('session_id', $validated['session_id'])
            ->where('riddle_id', $validated['riddle_id'])
            ->firstOrFail();

        // Trouver le GamePlayer correspondant à l'utilisateur courant
        $player = GamePlayer::where('session_id', $session->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (!$needsLock) {
            // Pas besoin de verrouiller : retourner succès directement
            return response()->json(['locked' => true, 'game_riddle_id' => $gameRiddle->id]);
        }

        // Appel de la fonction PostgreSQL atomique (évite toute race condition)
        $result = DB::selectOne(
            'SELECT lock_game_riddle(?, ?) AS result',
            [$gameRiddle->id, $player->id]
        );

        $data = json_decode($result->result, true);

        if (!$data['success']) {
            // Quelqu'un d'autre a verrouillé en même temps
            $locker = GamePlayer::with('user')->find($data['locked_by_player_id']);
            return response()->json([
                'locked'              => false,
                'locked_by_name'      => $locker?->user?->name ?? 'Un joueur',
                'message'             => "🔒 {$locker?->user?->name} vient de verrouiller cette énigme !",
            ], 409);
        }

        // Recharger et diffuser l'événement de verrouillage à tous les joueurs
        $gameRiddle->refresh();
        $player->load('user');

        event(new \App\Events\RiddleLocked($gameRiddle, $player, $session->lien_token));

        return response()->json([
            'locked'         => true,
            'game_riddle_id' => $gameRiddle->id,
        ]);
    }

    // Enregistre les points et les tentatives du joueur en continu
    public function recordResult(Request $request)
    {
        $validated = $request->validate([
            'session_id'      => 'required|integer',
            'riddle_id'       => 'required|integer',
            'status'          => 'required|string|in:gagne,perdu',
            'points'          => 'required|integer',
            'mode_choisi'     => 'required|string',
            'temps_resolution' => 'nullable|integer'
        ]);

        $gameRiddle = GameRiddle::where('session_id', $validated['session_id'])
            ->where('riddle_id', $validated['riddle_id'])
            ->first();

        if (!$gameRiddle) {
            $gameRiddle = GameRiddle::create([
                'session_id' => $validated['session_id'],
                'riddle_id'  => $validated['riddle_id'],
                'statut'     => 'ouvert',
            ]);
        }

        $session = GameSession::findOrFail($validated['session_id']);

        if ($session->statut === 'termine') {
            return response()->json([
                'success'          => false,
                'session_finished' => true,
                'message'          => 'Cette session est déjà terminée.',
            ]);
        }

        // Vérification de verrouillage selon le mode
        if ($session->type === 'participants') {
            $alreadyAttempted = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)
                ->exists();

            if ($alreadyAttempted) {
                return response()->json([
                    'success'        => false,
                    'already_solved' => true,
                    'message'        => "Désolé ! Un autre participant a déjà clôturé cette énigme !"
                ]);
            }

        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_membre') {
            $alreadyAttempted = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)
                ->exists();

            if ($alreadyAttempted) {
                return response()->json([
                    'success'        => false,
                    'already_solved' => true,
                    'message'        => "Trop tard ! Un challenger a déjà répondu à cette énigme."
                ]);
            }

        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_tous') {
            $alreadyAttemptedByUser = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)
                ->where('user_id', auth()->id())
                ->exists();

            if ($alreadyAttemptedByUser) {
                return response()->json([
                    'success'        => false,
                    'already_solved' => true,
                    'message'        => "Vous avez déjà répondu à cette énigme."
                ]);
            }
        }

        $pointsToAward = $validated['status'] === 'gagne' ? $validated['points'] : 0;

        GamePlayerRiddleAttempt::create([
            'game_session_id' => $validated['session_id'],
            'user_id'         => auth()->id(),
            'game_riddle_id'  => $gameRiddle->id,
            'mode_choisi'     => $validated['mode_choisi'],
            'status'          => $validated['status'],
            'points_earned'   => $pointsToAward,
            'time_limit'      => $validated['temps_resolution'] ?: 0,
            'started_at'      => now(),
        ]);

        if ($pointsToAward > 0) {
            \App\Models\Score::create([
                'session_id'      => $validated['session_id'],
                'user_id'         => auth()->id(),
                'points'          => $pointsToAward,
                'temps_resolution' => $validated['temps_resolution'] ?: 0
            ]);
        }

        // Marquer l'énigme comme résolue (si pas déjà verrouillée atomiquement)
        $gameRiddle->update([
            'repondu_par' => auth()->id(),
            'statut'      => 'verrouille',
        ]);

        $sessionFinished = false;

        if ($session->type === 'solo') {
            $userAttempts = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('user_id', auth()->id())
                ->count();

            if ($userAttempts >= (int) $session->riddles_count) {
                $session->update(['statut' => 'termine']);
                $sessionFinished = true;
            }

        } elseif ($session->type === 'participants' || ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_membre')) {
            $sessionRiddlesInPlay = GameRiddle::where('session_id', $session->id)->count();
            $targetAnswered       = min(max((int) $session->riddles_count, 0), $sessionRiddlesInPlay);

            if ($targetAnswered > 0) {
                $distinctAnswered = (int) DB::table('game_player_riddle_attempts')
                    ->where('game_session_id', $session->id)
                    ->selectRaw('count(distinct game_riddle_id) as cnt')
                    ->first()->cnt ?? 0;

                if ($distinctAnswered >= $targetAnswered) {
                    $session->update(['statut' => 'termine']);
                    $sessionFinished = true;
                }
            }

        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_tous') {
            $playersCount       = $session->players()->count();
            $sessionRiddlesCount = GameRiddle::where('session_id', $session->id)->count();
            $totalAttempts      = GamePlayerRiddleAttempt::where('game_session_id', $session->id)->count();

            if ($totalAttempts >= $playersCount * $sessionRiddlesCount) {
                $session->update(['statut' => 'termine']);
                $sessionFinished = true;
            }
        }

        $session->refresh();
        event(new \App\Events\GameUpdated($session));

        return response()->json([
            'success'          => true,
            'session_finished' => $sessionFinished,
        ]);
    }

    // Affiche le Lobby
    public function lobby($token)
    {
        $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();

        if ($session->type === 'solo' && $session->players->firstWhere('user_id', auth()->id())) {
            $session->update(['statut' => 'en_cours']);
            return redirect()->route('game.play', ['token' => $token]);
        }

        $currentPlayer = $session->players->firstWhere('user_id', auth()->id());

        if (!$currentPlayer && !auth()->user()->is_admin) {
            if ($session->players->count() < $session->max_joueurs) {
                $creatorPlayer = $session->players->first();
                $globalMode    = $creatorPlayer ? $creatorPlayer->global_mode : 'mixte';

                GamePlayer::create([
                    'session_id'  => $session->id,
                    'user_id'     => auth()->id(),
                    'statut'      => 'pret',
                    'global_mode' => $globalMode
                ]);

                $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();
                event(new \App\Events\LobbyUpdated($session));
            } else if ($session->type !== 'solo') {
                return redirect()->route('game.dashboard')->with('error', 'Désolé, cette session de jeu est déjà complète.');
            }
        }

        return Inertia::render('Game/Play/Lobby', ['session' => $session]);
    }

    // Démarre la session multijoueur
    public function startGame($token)
    {
        $session = GameSession::where('lien_token', $token)->firstOrFail();
        $session->update(['statut' => 'en_cours']);
        event(new \App\Events\LobbyUpdated($session));
        return redirect()->route('game.play', ['token' => $token]);
    }

    // Affiche l'interface de jeu dynamique
    public function play($token)
    {
        $session = GameSession::with([
            'gameRiddles.riddle.place.riddles',
            'gameRiddles.riddle.images',
            'gameRiddles.riddle.hints',
            'gameRiddles.lockedByPlayer.user',  // NOUVEAU
            'players.user',
            'attempts.gameRiddle',
            'attempts.user'
        ])->where('lien_token', $token)->firstOrFail();

        if ($session->statut === 'termine') {
            return redirect()->route('game.dashboard')
                ->with('success', 'Cette session de jeu est terminée. Bravo à l\'équipe !');
        }

        $session->load(['gameRiddles.riddle.hints', 'gameRiddles.riddle.images', 'gameRiddles.riddle.place']);

        $gameSteps = [];
        foreach ($session->gameRiddles as $gameRiddle) {
            $riddle = $gameRiddle->riddle;
            $place  = $riddle->place;

            $gameSteps[] = [
                'id'                   => $place->id,
                'nom'                  => $place->nom,
                'latitude'             => $place->lat,
                'longitude'            => $place->lng,
                'rayon_marge'          => $place->rayon_marge,
                'image'                => $place->image,
                'verified_description' => $place->verified_description,
                'riddle'               => $riddle,
                // NOUVEAU : état de verrouillage initial de l'énigme
                'game_riddle' => [
                    'id'                  => $gameRiddle->id,
                    'statut'              => $gameRiddle->statut,
                    'locked_by_player_id' => $gameRiddle->locked_by_player_id,
                    'locked_by_name'      => $gameRiddle->lockedByPlayer?->user?->name,
                ],
            ];
        }

        return Inertia::render('Game/Play/ActiveRiddle', [
            'session'   => $session,
            'gameSteps' => $gameSteps
        ]);
    }

    // Récupère les indices pour une énigme donnée
    public function getHints($riddleId)
    {
        $riddle = Riddle::findOrFail($riddleId);
        $hints  = $riddle->hints()
            ->select('id', 'type', 'content', 'difficulty_level', 'order')
            ->orderBy('order')
            ->get();

        return response()->json(['success' => true, 'hints' => $hints]);
    }
}
