<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Riddle;
use App\Models\User;
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
            'recent_places' => Place::latest()->take(5)->get(),
        ]);
    }

    public function places()
    {
        return Inertia::render('Admin/Places', [
            'places' => Place::withCount('riddles')->latest()->get(),
        ]);
    }

    public function storePlace(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'departement' => 'required|string|max:255',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'rayon_marge' => 'required|integer',
        ]);

        Place::create($validated);

        return back()->with('success', 'Lieu créé avec succès.');
    }

    public function togglePlace(Place $place)
    {
        $place->update(['is_active' => !$place->is_active]);
        return back();
    }

    public function riddles(Place $place)
    {
        return Inertia::render('Admin/Enigmas', [
            'place' => $place,
            'enigmas' => $place->riddles()->orderBy('niveau')->get(),
        ]);
    }

    public function storeRiddle(Request $request, Place $place)
    {
        $validated = $request->validate([
            'niveau' => 'required|integer|between:1,3',
            'description' => 'required|string',
            'reponse' => 'required|string|max:255',
            'photos' => 'nullable|array',
        ]);

        $place->riddles()->create($validated);

        return back()->with('success', 'Énigme ajoutée.');
    }
}
