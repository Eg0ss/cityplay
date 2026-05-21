<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Riddle;
use App\Models\User;
use App\Models\City;
use App\Http\Requests\RiddleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    //dashboard admin
    public function dashboard()
    {
        $totalRiddles = Riddle::count();
        // Compter le nombre d'énigmes uniques ayant au moins une tentative réussie
        $solvedRiddles = Riddle::whereHas('gameRiddles.attempts', function($q) {
            $q->where('status', 'gagne');
        })->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users_count' => User::count(),
                'places_count' => Place::count(),
                'riddles_count' => $totalRiddles,
                'solved_count' => $totalRiddles > 0 ? round(($solvedRiddles / $totalRiddles) * 100) : 0,
            ],
            'recent_places' => Place::with('city')->latest()->take(5)->get(),
        ]);
    }

    // Toutes les villes
    public function cities()
    {
        return Inertia::render('Admin/Cities', [
            'cities' => City::withCount('places')->orderBy('name')->get(),
        ]);
    }

    // Tous les lieux
    public function allPlaces()
    {
        return Inertia::render('Admin/AllPlaces', [
            'places' => Place::with('city')->withCount('riddles')->latest()->get(),
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    // Toutes les énigmes
    public function allEnigmas()
    {
        return Inertia::render('Admin/AllEnigmas', [
            'enigmas' => Riddle::with('place.city', 'images')->latest()->get(),
            'places' => Place::orderBy('nom')->get(),
        ]);
    }

    // Ajouter une ville
    public function storeCity(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'departement' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = str()->slug($validated['name']);

        City::create($validated);

        return back()->with('success', 'Ville ajoutée à la matrice.');
    }

    // Modifier une ville
    public function updateCity(Request $request, City $city)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'departement' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = str()->slug($validated['name']);

        $city->update($validated);

        return back()->with('success', 'Ville mise à jour dans la matrice.');
    }

    // Supprimer une ville
    public function deleteCity(City $city)
    {
        $city->delete();
        return back()->with('success', 'Ville supprimée de la matrice.');
    }

    // Tous les lieux d'une ville
    public function places(City $city)
    {
        return Inertia::render('Admin/Places', [
            'city' => $city,
            'places' => $city->places()->withCount('riddles')->latest()->get(),
        ]);
    }

    // Ajouter un lieu
    public function storePlace(Request $request, City $city)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'verified_description' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'rayon_marge' => 'required|integer',
            'image' => 'nullable|file|max:2048',
        ]);

        // Stocker l'image si elle est fournie
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('places', 'public');
        }

        $city->places()->create($validated);

        return back()->with('success', 'Balise GPS déployée avec succès.');
    }

    // Modifier un lieu
    public function updatePlace(Request $request, Place $place)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'verified_description' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'rayon_marge' => 'required|integer',
            'image' => 'nullable|file|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('places', 'public');
        }

        $place->update($validated);

        return back()->with('success', 'Lieu mis à jour avec succès.');
    }

    // Supprimer un lieu
    public function deletePlace(Place $place)
    {
        $place->delete();
        return back()->with('success', 'Lieu supprimé avec succès.');
    }

    // Supprimer une énigme
    public function deleteEnigma(Riddle $enigma)
    {
        $enigma->delete();
        return back()->with('success', 'Énigme supprimée avec succès.');
    }

    // Ajouter un lieu depuis la vue globale
    public function storeGlobalPlace(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'nom' => 'required|string|max:255',
            'verified_description' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'rayon_marge' => 'required|integer',
            'image' => 'nullable|file|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('places', 'public');
        }

        Place::create($validated);

        return back()->with('success', 'Balise GPS déployée avec succès dans la cité sélectionnée.');
    }

    // Activer ou désactiver un lieu
    public function togglePlace(Place $place)
    {
        $place->update(['is_active' => !$place->is_active]);
        return back();
    }

    // Toutes les énigmes d'un lieu
    public function riddles(Place $place)
    {
        return Inertia::render('Admin/Enigmas', [
            'place' => $place->load('city'),
            'enigmas' => $place->riddles()->with('images', 'hints')->orderBy('niveau')->get(),
        ]);
    }

    // Ajouter une énigme
    public function storeRiddle(RiddleRequest $request, Place $place)
    {
        $validated = $request->validate([
            'niveau' => 'required|integer|between:1,3',
            'description' => 'required|string',
            'reponse' => 'required|string|max:255',
            'mcq_options' => 'nullable|array',
            'hint_keyword' => 'nullable|string|max:255',
            'hint_images.*' => 'nullable|file|max:2048',
        ]);

        // Filtrer les options vides pour ne pas stocker de tableau de chaînes vides
        if (isset($validated['mcq_options'])) {
            $validated['mcq_options'] = array_values(array_filter($validated['mcq_options'], function($val) {
                return !is_null($val) && trim($val) !== '';
            }));
            
            if (empty($validated['mcq_options'])) {
                $validated['mcq_options'] = null;
            }
        }

        $riddle = $place->riddles()->create([
            'niveau' => $validated['niveau'],
            'description' => $validated['description'],
            'reponse' => $validated['reponse'],
            'mcq_options' => $validated['mcq_options'] ?? null,
        ]);

        // Sauvegarder le mot-clé d'indice
        if (!empty($validated['hint_keyword'])) {
            $riddle->hints()->create([
                'type' => 'keyword',
                'content' => $validated['hint_keyword'],
                'difficulty_level' => 'easy',
                'order' => 1,
            ]);
        }

        // Sauvegarder les images d'indice
        if ($request->hasFile('hint_images')) {
            foreach ($request->file('hint_images') as $index => $image) {
                $path = $image->store('hints', 'public');
                $riddle->hints()->create([
                    'type' => 'image',
                    'content' => '/storage/' . $path,
                    'difficulty_level' => 'medium',
                    'order' => $index + 2,
                ]);
            }
        }

        return back()->with('success', 'Énigme ajoutée à la matrice.');
    }

    // Modifier une énigme
    public function updateRiddle(RiddleRequest $request, Riddle $enigma)
    {
        $validated = $request->validate([
            'niveau' => 'required|integer|between:1,3',
            'description' => 'required|string',
            'reponse' => 'required|string|max:255',
            'mcq_options' => 'nullable|array',
            'hint_keyword' => 'nullable|string|max:255',
            'hint_images.*' => 'nullable|file|max:2048',
        ]);

        if (isset($validated['mcq_options'])) {
            $validated['mcq_options'] = array_values(array_filter($validated['mcq_options'], function($val) {
                return !is_null($val) && trim($val) !== '';
            }));
            
            if (empty($validated['mcq_options'])) {
                $validated['mcq_options'] = null;
            }
        }

        $enigma->update([
            'niveau' => $validated['niveau'],
            'description' => $validated['description'],
            'reponse' => $validated['reponse'],
            'mcq_options' => $validated['mcq_options'] ?? null,
        ]);

        // Mettre à jour les indices
        if (!empty($validated['hint_keyword']) || $request->hasFile('hint_images')) {
            // Pour simplifier, on supprime les anciens indices de type keyword/image
            $enigma->hints()->whereIn('type', ['keyword', 'image'])->delete();

            if (!empty($validated['hint_keyword'])) {
                $enigma->hints()->create([
                    'type' => 'keyword',
                    'content' => $validated['hint_keyword'],
                    'difficulty_level' => 'easy',
                    'order' => 1,
                ]);
            }

            if ($request->hasFile('hint_images')) {
                foreach ($request->file('hint_images') as $index => $image) {
                    $path = $image->store('hints', 'public');
                    $enigma->hints()->create([
                        'type' => 'image',
                        'content' => '/storage/' . $path,
                        'difficulty_level' => 'medium',
                        'order' => $index + 2,
                    ]);
                }
            }
        }

        return back()->with('success', 'Énigme mise à jour avec succès.');
    }

    // Générer un lien de session rapide pour un lieu
    public function generateSession(Place $place)
    {
        $token = str()->random(10);
        $session = \App\Models\GameSession::create([
            'statut' => 'en_attente',
            'lien_token' => $token,
            'max_joueurs' => 10,
            'level' => 'facile',
            'location_type' => 'place',
            'location_id' => $place->id,
            'riddles_count' => $place->riddles()->count(),
            'type' => 'participants',
        ]);

        \App\Models\GamePlayer::create([
            'session_id' => $session->id,
            'user_id' => auth()->id(),
            'statut' => 'pret',
            'global_mode' => 'mixte'
        ]);

        return redirect()->route('game.lobby', ['token' => $token])->with('success', 'Lien de session généré pour ce lieu !');
    }
}
