<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\GameSession;
use App\Models\GamePlayer;
use App\Models\GameRiddle;
use App\Models\Riddle;
use App\Models\GamePlayerRiddleAttempt;
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

    // Affiche le processus de configuration de partie
    public function setup()
    {
        return Inertia::render('Game/Setup/Index');
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

        // Ajouter le créateur comme joueur
        GamePlayer::create([
            'session_id' => $session->id,
            'user_id' => auth()->id(),
            'statut' => 'pret',
            'global_mode' => $validated['global_mode']
        ]);

        $levelMapping = [
            'facile' => 1,
            'intermediaire' => 2,
            'difficile' => 3
        ];
        $niveauInt = $levelMapping[$validated['level']];

        // 1. Trouver les $riddles_count lieux les plus proches qui ONT des énigmes de ce niveau
        $lat = $validated['user_lat'];
        $lng = $validated['user_lng'];

        $closestPlaces = \App\Models\Place::select('places.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            )
            ->whereHas('riddles', function($query) use ($niveauInt) {
                $query->where('niveau', $niveauInt);
            })
            ->orderBy('distance', 'asc')
            ->limit($validated['riddles_count'])
            ->get();

        // 2. Pour chaque lieu proche, sélectionner aléatoirement 1 énigme du bon niveau
        $riddles = collect();
        foreach ($closestPlaces as $place) {
            $riddle = Riddle::where('place_id', $place->id)
                ->where('niveau', $niveauInt)
                ->inRandomOrder()
                ->first();
            
            if ($riddle) {
                $riddles->push($riddle);
            }
        }

        foreach ($riddles as $riddle) {
            GameRiddle::create([
                'session_id' => $session->id,
                'riddle_id' => $riddle->id,
            ]);
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

        GamePlayerRiddleAttempt::create([
            'game_session_id' => $validated['session_id'],
            'user_id' => auth()->id(),
            'game_riddle_id' => $gameRiddle->id,
            'mode_choisi' => $validated['mode_choisi'],
            'status' => $validated['status'],
            'points_earned' => $validated['points'],
            'time_limit' => $validated['temps_resolution'] ?: 0,
            'started_at' => now(),
        ]);

        if ($validated['status'] === 'gagne') {
            \App\Models\Score::create([
                'session_id' => $validated['session_id'],
                'user_id' => auth()->id(),
                'points' => $validated['points'],
                'temps_resolution' => $validated['temps_resolution'] ?: 0
            ]);
        }

        return response()->json(['success' => true]);
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
                GamePlayer::create([
                    'session_id' => $session->id,
                    'user_id' => auth()->id(),
                    'statut' => 'pret',
                    'global_mode' => 'gaming' // Mode par défaut
                ]);
                
                // Recharger la session avec le nouveau joueur
                $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();
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
        return redirect()->route('game.play', ['token' => $token]);
    }

    // Affiche l'interface de jeu dynamique
    public function play($token)
    {
        $session = GameSession::with(['gameRiddles.riddle.place', 'players.user'])
            ->where('lien_token', $token)
            ->firstOrFail();

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
}
