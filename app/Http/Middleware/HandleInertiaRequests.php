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
        if ($user) {
            // Calculer les points totaux du joueur
            $totalPoints = \App\Models\Score::where('user_id', $user->id)->sum('points');
            $user->total_points = $totalPoints;
            
            // Calculer le niveau dynamique
            if ($totalPoints < 500) {
                $user->level_name = 'BRONZE I';
            } elseif ($totalPoints < 1500) {
                $user->level_name = 'BRONZE II';
            } elseif ($totalPoints < 3000) {
                $user->level_name = 'ARGENT I';
            } else {
                $user->level_name = 'OR I';
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
        ];
    }
}
