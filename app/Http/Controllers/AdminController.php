<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Enigma;
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
                'enigmas_count' => Enigma::count(),
                'solved_count' => 0, // Placeholder until solved tracking is added
            ],
            'recent_places' => Place::latest()->take(5)->get(),
        ]);
    }

    public function places()
    {
        return Inertia::render('Admin/Places', [
            'places' => Place::withCount('enigmas')->latest()->get(),
        ]);
    }

    public function storePlace(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        Place::create($validated);

        return back()->with('success', 'Lieu créé avec succès.');
    }

    public function togglePlace(Place $place)
    {
        $place->update(['is_active' => !$place->is_active]);
        return back();
    }

    public function enigmas(Place $place)
    {
        return Inertia::render('Admin/Enigmas', [
            'place' => $place,
            'enigmas' => $place->enigmas()->orderBy('level')->get(),
        ]);
    }

    public function storeEnigma(Request $request, Place $place)
    {
        $validated = $request->validate([
            'level' => 'required|integer|between:1,3',
            'description' => 'required|string',
            'answer' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $place->enigmas()->create($validated);

        return back()->with('success', 'Énigme ajoutée.');
    }
}
