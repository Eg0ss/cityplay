<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Riddle;
use App\Models\User;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users_count' => User::count(),
                'places_count' => Place::count(),
                'riddles_count' => Riddle::count(),
                'solved_count' => 0,
            ],
            'recent_places' => Place::with('city')->latest()->take(5)->get(),
        ]);
    }

    public function cities()
    {
        return Inertia::render('Admin/Cities', [
            'cities' => City::withCount('places')->orderBy('name')->get(),
        ]);
    }

    public function storeCity(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'departement' => 'required|string|max:255',
        ]);

        $validated['slug'] = str()->slug($validated['name']);

        City::create($validated);

        return back()->with('success', 'Ville ajoutée à la matrice.');
    }

    public function places(City $city)
    {
        return Inertia::render('Admin/Places', [
            'city' => $city,
            'places' => $city->places()->withCount('riddles')->latest()->get(),
        ]);
    }

    public function storePlace(Request $request, City $city)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'verified_description' => 'nullable|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'rayon_marge' => 'required|integer',
        ]);

        $city->places()->create($validated);

        return back()->with('success', 'Balise GPS déployée avec succès.');
    }

    public function togglePlace(Place $place)
    {
        $place->update(['is_active' => !$place->is_active]);
        return back();
    }

    public function riddles(Place $place)
    {
        return Inertia::render('Admin/Enigmas', [
            'place' => $place->load('city'),
            'enigmas' => $place->riddles()->with('images')->orderBy('niveau')->get(),
        ]);
    }

    public function storeRiddle(Request $request, Place $place)
    {
        $validated = $request->validate([
            'niveau' => 'required|integer|between:1,3',
            'description' => 'required|string',
            'reponse' => 'required|string|max:255',
            'mcq_options' => 'nullable|array',
            'images.*' => 'nullable|image|max:2048',
        ]);

        $riddle = $place->riddles()->create([
            'niveau' => $validated['niveau'],
            'description' => $validated['description'],
            'reponse' => $validated['reponse'],
            'mcq_options' => $validated['mcq_options'] ?? null,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('riddles', 'public');
                $riddle->images()->create(['image_path' => $path]);
            }
        }

        return back()->with('success', 'Énigme ajoutée à la matrice.');
    }
}
