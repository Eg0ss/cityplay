<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $userData = null;
        if ($user) {
            // Calculer les points totaux du joueur
            $totalPoints = \App\Models\Score::where('user_id', $user->id)->sum('points');
            
            // Calculer le niveau dynamique
            if ($totalPoints < 500) {
                $levelName = 'BRONZE I';
            } elseif ($totalPoints < 1500) {
                $levelName = 'BRONZE II';
            } elseif ($totalPoints < 3000) {
                $levelName = 'ARGENT I';
            } else {
                $levelName = 'OR I';
            }

            // Exporter en tableau et fusionner les propriétés calculées sans polluer le modèle Eloquent
            $userData = array_merge($user->toArray(), [
                'total_points' => $totalPoints,
                'level_name' => $levelName,
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
            ],
        ];
    }
}
