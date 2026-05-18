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
    // Affiche le Dashboard Gaming
    public function dashboard()
    {
        // Récupérer les statistiques du joueur connecté
        return Inertia::render('Game/Dashboard', [
            'history' => [] // À compléter
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

        // 1. Trouver les $riddles_count lieux les plus proches grâce à la formule de Haversine
        $lat = $validated['user_lat'];
        $lng = $validated['user_lng'];

        $closestPlaces = \App\Models\Place::select('places.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            )
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

    // Affiche le Lobby (salle d'attente Multijoueur) ou lance directement si Solo
    public function lobby($token)
    {
        $session = GameSession::with('players.user')->where('lien_token', $token)->firstOrFail();
        
        if ($session->type === 'solo') {
            // Lancer la partie directement
            $session->update(['statut' => 'en_cours']);
            return redirect()->route('game.play', ['token' => $token]);
        }

        return Inertia::render('Game/Play/Lobby', [
            'session' => $session
        ]);
    }

    // Affiche l'interface de jeu dynamique
    public function play($token)
    {
        $session = GameSession::with(['gameRiddles.riddle.place', 'players.user'])
            ->where('lien_token', $token)
            ->firstOrFail();

        return Inertia::render('Game/Play/ActiveRiddle', [
            'session' => $session
        ]);
    }
}
