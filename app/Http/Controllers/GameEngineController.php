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
    // ── Dashboard ─────────────────────────────────────────────────────────────
    public function dashboard()
    {
        $userId        = auth()->id();
        $totalGames    = GamePlayer::where('user_id', $userId)->count();
        $riddlesSolved = GamePlayerRiddleAttempt::where('user_id', $userId)->where('status', 'gagne')->count();
        $totalPoints   = (int) \App\Models\Score::where('user_id', $userId)->sum('points');

        // Sessions en pause de ce joueur → "Aventures non terminées"
        $pausedSessions = GameSession::with(['players.user'])
            ->where('statut', 'en_pause')
            ->whereHas('players', fn($q) => $q->where('user_id', $userId))
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'lien_token'    => $s->lien_token,
                'level'         => $s->level,
                'type'          => $s->type,
                'riddles_count' => $s->riddles_count,
                'updated_at'    => $s->updated_at->diffForHumans(),
            ]);

        return Inertia::render('Game/Dashboard', [
            'stats' => [
                'total_games'    => $totalGames,
                'riddles_solved' => $riddlesSolved,
                'total_points'   => $totalPoints,
            ],
            'pausedSessions' => $pausedSessions,
        ]);
    }

    // ── Progression ───────────────────────────────────────────────────────────
    public function progression()
    {
        $userId      = auth()->id();
        $totalGames  = GamePlayer::where('user_id', $userId)->count();
        $attempts    = GamePlayerRiddleAttempt::where('user_id', $userId)->get();
        $solvedCount = $attempts->where('status', 'gagne')->count();
        $failedCount = $attempts->where('status', 'perdu')->count();
        $totalPoints = (int) \App\Models\Score::where('user_id', $userId)->sum('points');

        $recentAttempts = GamePlayerRiddleAttempt::with(['gameRiddle.riddle.place', 'session'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(fn($attempt) => [
                'id'            => $attempt->id,
                'status'        => $attempt->status,
                'points_earned' => $attempt->points_earned,
                'riddle_title'  => $attempt->gameRiddle?->riddle?->description ?? 'Énigme',
                'place_name'    => $attempt->gameRiddle?->riddle?->place?->nom ?? 'Lieu inconnu',
                'date'          => $attempt->created_at->format('d/m/Y H:i'),
            ]);

        // Niveaux avec nouveaux seuils XP demandés
        $levelName       = 'Aspirant 🌱';
        $nextLevelName   = 'Explorateur 🦁';
        $xpMin           = 0;
        $xpMax           = 200;

        if ($totalPoints >= 1_000_000) {
            $levelName     = 'Légende du Bénin 👑';
            $nextLevelName = 'Niveau Maximum';
            $xpMin         = 1_000_000;
            $xpMax         = 1_000_000;
        } elseif ($totalPoints >= 100_000) {
            $levelName     = 'Passionné d\'XP ⚡';
            $nextLevelName = 'Légende du Bénin 👑';
            $xpMin         = 100_000;
            $xpMax         = 1_000_000;
        } elseif ($totalPoints >= 50_000) {
            $levelName     = 'Chasseur d\'Énigmes 🕵️';
            $nextLevelName = 'Passionné d\'XP ⚡';
            $xpMin         = 50_000;
            $xpMax         = 100_000;
        } elseif ($totalPoints >= 200) {
            $levelName     = 'Explorateur 🦁';
            $nextLevelName = 'Chasseur d\'Énigmes 🕵️';
            $xpMin         = 200;
            $xpMax         = 50_000;
        }

        $progressPercent = ($xpMax > $xpMin)
            ? min(100, (int) round((($totalPoints - $xpMin) / ($xpMax - $xpMin)) * 100))
            : 100;

        return Inertia::render('Game/Progression', [
            'levelName'       => $levelName,
            'nextLevelName'   => $nextLevelName,
            'totalPoints'     => $totalPoints,
            'xpMin'           => $xpMin,
            'xpMax'           => $xpMax,
            'progressPercent' => $progressPercent,
            'stats'           => [
                'total_games'  => $totalGames,
                'solved_count' => $solvedCount,
                'failed_count' => $failedCount,
            ],
            'recentAttempts'  => $recentAttempts,
            'badges'          => [
                [
                    'id'          => 'first_step',
                    'title'       => 'Premier Pas 🗺️',
                    'description' => '1ère session terminée.',
                    'unlocked'    => $totalGames >= 1,
                ],
                [
                    'id'          => 'riddle_hunter',
                    'title'       => 'Chasseur d\'Énigmes 🕵️',
                    'description' => '50 000 XP cumulés.',
                    'unlocked'    => $totalPoints >= 50_000,
                ],
                [
                    'id'          => 'xp_enthusiast',
                    'title'       => 'Passionné d\'XP ⚡',
                    'description' => '100 000 XP cumulés.',
                    'unlocked'    => $totalPoints >= 100_000,
                ],
                [
                    'id'          => 'benin_legend',
                    'title'       => 'Légende du Bénin 👑',
                    'description' => '1 000 000 XP cumulés.',
                    'unlocked'    => $totalPoints >= 1_000_000,
                ],
            ],
        ]);
    }

    // ── Setup ─────────────────────────────────────────────────────────────────
    public function setup()
    {
        $cities = \App\Models\City::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($city) {
                $placeIds       = $city->places->pluck('id');
                $riddlesCount   = Riddle::whereIn('place_id', $placeIds)->count();
                $riddlesByLevel = Riddle::whereIn('place_id', $placeIds)
                    ->selectRaw('niveau, COUNT(*) as count')
                    ->groupBy('niveau')
                    ->pluck('count', 'niveau');

                return [
                    'id'               => $city->id,
                    'name'             => $city->name,
                    'departement'      => $city->departement,
                    'riddles_count'    => $riddlesCount,
                    'riddles_by_level' => [
                        'facile'        => (int) $riddlesByLevel->get(1, 0),
                        'intermediaire' => (int) $riddlesByLevel->get(2, 0),
                        'difficile'     => (int) $riddlesByLevel->get(3, 0),
                    ],
                ];
            });

        return Inertia::render('Game/Setup/Index', ['cities' => $cities]);
    }

    // ── Créer session ─────────────────────────────────────────────────────────
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
        $lat          = $validated['user_lat'];
        $lng          = $validated['user_lng'];

        // Sélectionner les lieux triés par distance croissante (= 1ère énigme = lieu le plus proche)
        $closestPlaces = \App\Models\Place::select('places.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            )
            ->where('city_id', $validated['location_id'])
            ->where('is_active', true)
            ->whereHas('riddles', fn($q) => $q->where('niveau', $niveauInt))
            ->orderBy('distance', 'asc')
            ->get();

        if ($closestPlaces->isEmpty()) {
            return redirect()->back()->with('error',
                'Pas d\'énigme disponible pour le niveau ' . $validated['level'] . ' dans cette ville.'
            );
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

        // Créer le joueur si l'utilisateur participe
        if (!isset($validated['participate']) || $validated['participate']) {
            GamePlayer::create([
                'session_id'  => $session->id,
                'user_id'     => auth()->id(),
                'statut'      => 'pret',
                'global_mode' => $validated['global_mode'] ?: 'mixte',
            ]);
        }

        // Créer les GameRiddle dans l'ordre de distance (lieu le plus proche en premier)
        $usedRiddleIds  = collect();
        $riddlesCreated = 0;
        $targetCount    = (int) $validated['riddles_count'];
        $placeIndex     = 0;
        $loops          = 0;

        while ($riddlesCreated < $targetCount && $loops < 10) {
            if ($placeIndex >= $closestPlaces->count()) {
                $placeIndex = 0;
                $loops++;
            }

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
        }

        if ($riddlesCreated === 0) {
            $session->delete();
            return redirect()->back()->with('error', 'Impossible de créer des énigmes pour cette configuration.');
        }

        if ($riddlesCreated < $targetCount) {
            $session->update(['riddles_count' => $riddlesCreated]);
        }

        return redirect()->route('game.lobby', ['token' => $token]);
    }

    // ── Lock énigme ───────────────────────────────────────────────────────────
    public function lockRiddle(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer',
            'riddle_id'  => 'required|integer',
        ]);

        $session    = GameSession::findOrFail($validated['session_id']);
        $needsLock  = in_array($session->type, ['participants', 'challengers'])
            && $session->challenger_mode !== 'reponse_par_tous';

        $gameRiddle = GameRiddle::where('session_id', $validated['session_id'])
            ->where('riddle_id', $validated['riddle_id'])
            ->firstOrFail();

        $player = GamePlayer::where('session_id', $session->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (!$needsLock) {
            return response()->json(['locked' => true, 'game_riddle_id' => $gameRiddle->id]);
        }

        $result = DB::selectOne('SELECT lock_game_riddle(?, ?) AS result', [$gameRiddle->id, $player->id]);
        $data   = json_decode($result->result, true);

        if (!$data['success']) {
            $locker = GamePlayer::with('user')->find($data['locked_by_player_id']);
            return response()->json([
                'locked'         => false,
                'locked_by_name' => $locker?->user?->name ?? 'Un joueur',
                'message'        => '🔒 ' . ($locker?->user?->name ?? 'Un joueur') . ' vient de verrouiller cette énigme !',
            ], 409);
        }

        $gameRiddle->refresh();
        event(new \App\Events\RiddleLocked($gameRiddle, $player, $session->lien_token));

        return response()->json(['locked' => true, 'game_riddle_id' => $gameRiddle->id]);
    }

    // ── Enregistrer résultat ──────────────────────────────────────────────────
    //
    // CORRECTIONS :
    // 1. mode_choisi : suppression de 'in:gaming,decouverte' — en mode difficile
    //    sans MCQ le front peut envoyer 'gaming' ou null → on accepte nullable
    // 2. Colonnes exactes : time_limit (pas temps_resolution), status (pas statut)
    // 3. Score::create utilise temps_resolution (nom de colonne dans scores)
    //
    public function recordResult(Request $request)
    {
        $validated = $request->validate([
            'session_id'       => 'required|integer',
            'riddle_id'        => 'required|integer',
            'status'           => 'required|string|in:gagne,perdu',
            'points'           => 'required|integer',
            'mode_choisi'      => 'nullable|string',   // ← nullable, plus de restriction in:
            'transport_mode'   => 'nullable|string',
            'temps_resolution' => 'nullable|integer',
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

        // Anti-doublon selon le mode de jeu
        if ($session->type === 'participants') {
            $alreadyAttempted = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)->exists();
            if ($alreadyAttempted) {
                return response()->json(['success' => false, 'already_solved' => true,
                    'message' => 'Désolé ! Un participant a déjà clôturé cette énigme !']);
            }
        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_membre') {
            $alreadyAttempted = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)->exists();
            if ($alreadyAttempted) {
                return response()->json(['success' => false, 'already_solved' => true,
                    'message' => 'Trop tard ! Un challenger a déjà répondu à cette énigme.']);
            }
        } elseif ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_tous') {
            $alreadyAttemptedByUser = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('game_riddle_id', $gameRiddle->id)
                ->where('user_id', auth()->id())->exists();
            if ($alreadyAttemptedByUser) {
                return response()->json(['success' => false, 'already_solved' => true,
                    'message' => 'Vous avez déjà répondu à cette énigme.']);
            }
        }

        $pointsToAward = $validated['status'] === 'gagne' ? $validated['points'] : 0;

        // Colonnes exactes de game_player_riddle_attempts :
        // game_session_id | user_id | game_riddle_id | mode_choisi | transport_mode
        // time_limit | started_at | total_paused_time | last_paused_at | status | points_earned
        GamePlayerRiddleAttempt::create([
            'game_session_id' => $validated['session_id'],
            'user_id'         => auth()->id(),
            'game_riddle_id'  => $gameRiddle->id,
            'mode_choisi'     => $validated['mode_choisi'] ?? 'gaming',
            'transport_mode'  => $validated['transport_mode'] ?? null,
            'time_limit'      => $validated['temps_resolution'] ?? 0,
            'started_at'      => now(),
            'status'          => $validated['status'],
            'points_earned'   => $pointsToAward,
        ]);

        if ($pointsToAward > 0) {
            \App\Models\Score::create([
                'session_id'       => $validated['session_id'],
                'user_id'          => auth()->id(),
                'points'           => $pointsToAward,
                'temps_resolution' => $validated['temps_resolution'] ?? 0,
            ]);
        }

        $gameRiddle->update([
            'repondu_par' => auth()->id(),
            'statut'      => 'verrouille',
        ]);

        // Vérifier si la session est terminée
        $sessionFinished = false;

        if ($session->type === 'solo') {
            $userAttempts = GamePlayerRiddleAttempt::where('game_session_id', $session->id)
                ->where('user_id', auth()->id())->count();
            if ($userAttempts >= (int) $session->riddles_count) {
                $session->update(['statut' => 'termine']);
                $sessionFinished = true;
            }
        } elseif ($session->type === 'participants'
            || ($session->type === 'challengers' && $session->challenger_mode === 'reponse_par_membre')) {
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
            $playersCount        = $session->players()->count();
            $sessionRiddlesCount = GameRiddle::where('session_id', $session->id)->count();
            $totalAttempts       = GamePlayerRiddleAttempt::where('game_session_id', $session->id)->count();
            if ($totalAttempts >= $playersCount * $sessionRiddlesCount) {
                $session->update(['statut' => 'termine']);
                $sessionFinished = true;
            }
        }

        $session->refresh();
        event(new \App\Events\GameUpdated($session));

        return response()->json(['success' => true, 'session_finished' => $sessionFinished]);
    }

    // ── Pause session (mode découverte uniquement) ─────────────────────────────
    // Enregistre la session comme 'en_pause' → elle apparaît dans le dashboard
    // sous "Aventures non terminées". La reprise restaure l'état exact via localStorage.
    public function pauseSession(Request $request)
    {
        $validated = $request->validate(['session_id' => 'required|integer']);
        $session   = GameSession::findOrFail($validated['session_id']);

        $isPlayer = GamePlayer::where('session_id', $session->id)
            ->where('user_id', auth()->id())->exists();

        if (!$isPlayer) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        if (in_array($session->statut, ['en_cours', 'en_attente'])) {
            $session->update(['statut' => 'en_pause']);
        }

        return response()->json(['success' => true]);
    }

    // ── Reprendre une session en pause ────────────────────────────────────────
    public function resumeSession($token)
    {
        $session = GameSession::where('lien_token', $token)->firstOrFail();

        $isPlayer = GamePlayer::where('session_id', $session->id)
            ->where('user_id', auth()->id())->exists();

        if (!$isPlayer) {
            return redirect()->route('game.dashboard')
                ->with('error', 'Vous ne faites pas partie de cette session.');
        }

        if ($session->statut === 'en_pause') {
            $session->update(['statut' => 'en_cours']);
        }

        return redirect()->route('game.play', ['token' => $token]);
    }

    // ── Lobby ──────────────────────────────────────────────────────────────────
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
                GamePlayer::create([
                    'session_id'  => $session->id,
                    'user_id'     => auth()->id(),
                    'statut'      => 'pret',
                    'global_mode' => $creatorPlayer?->global_mode ?? 'mixte',
                ]);
                $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();
                event(new \App\Events\LobbyUpdated($session));
            } else {
                return redirect()->route('game.dashboard')->with('error', 'Cette session est déjà complète.');
            }
        }

        return Inertia::render('Game/Play/Lobby', ['session' => $session]);
    }

    // ── Démarrer la partie multi ───────────────────────────────────────────────
    public function startGame($token)
    {
        $session = GameSession::where('lien_token', $token)->firstOrFail();
        $session->update(['statut' => 'en_cours']);
        event(new \App\Events\LobbyUpdated($session));
        return redirect()->route('game.play', ['token' => $token]);
    }

    // ── Zone de jeu ───────────────────────────────────────────────────────────
    public function play($token)
    {
        $session = GameSession::with([
            'gameRiddles.riddle.place',
            'gameRiddles.riddle.images',
            'gameRiddles.riddle.hints',
            'gameRiddles.lockedByPlayer.user',
            'players.user',
            'attempts.gameRiddle',
            'attempts.user',
        ])->where('lien_token', $token)->firstOrFail();

        if ($session->statut === 'termine') {
            return redirect()->route('game.dashboard')->with('success', 'Session terminée. Bravo !');
        }

        // gameSteps dans l'ordre de création des GameRiddle (= ordre distance croissante)
        $gameSteps = $session->gameRiddles()
            ->orderBy('id', 'asc')
            ->with(['riddle.place', 'riddle.images', 'riddle.hints', 'lockedByPlayer.user'])
            ->get()
            ->map(fn($gr) => [
                'id'                   => $gr->riddle->place->id,
                'nom'                  => $gr->riddle->place->nom,
                'latitude'             => $gr->riddle->place->lat,
                'longitude'            => $gr->riddle->place->lng,
                'rayon_marge'          => $gr->riddle->place->rayon_marge,
                'marge_validation_gps' => $gr->riddle->place->marge_validation_gps,
                'image'                => $gr->riddle->place->image,
                'verified_description' => $gr->riddle->place->verified_description,
                'riddle'               => $gr->riddle,
                'game_riddle' => [
                    'id'                  => $gr->id,
                    'statut'              => $gr->statut,
                    'locked_by_player_id' => $gr->locked_by_player_id,
                    'locked_by_name'      => $gr->lockedByPlayer?->user?->name,
                ],
            ])->values()->toArray();

        return Inertia::render('Game/Play/ActiveRiddle', [
            'session'   => $session,
            'gameSteps' => $gameSteps,
        ]);
    }

    // ── Indices ───────────────────────────────────────────────────────────────
    public function getHints($riddleId)
    {
        $riddle = Riddle::findOrFail($riddleId);
        $hints  = $riddle->hints()->select('id', 'type', 'content', 'difficulty_level', 'order')->orderBy('order')->get();
        return response()->json(['success' => true, 'hints' => $hints]);
    }
}