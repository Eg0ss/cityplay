<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\Riddle;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GameController extends Controller
{
    public function createSession(Request $request)
    {
        $session = GameSession::create([
            'mode' => $request->mode ?? 'solo',
            'statut' => 'en_attente',
            'lien_token' => Str::random(32),
            'max_joueurs' => $request->max_joueurs ?? 1,
        ]);

        $session->players()->create([
            'user_id' => $request->user()->id,
            'mode_choisi' => $request->mode_choisi ?? 'precis',
            'statut' => 'pret',
        ]);

        return redirect()->route('game.session', ['token' => $session->lien_token]);
    }

    public function showSession($token)
    {
        $session = GameSession::where('lien_token', $token)
            ->with(['players.user', 'gameRiddles.riddle', 'scores.user'])
            ->firstOrFail();

        return Inertia::render('Game/Session', [
            'session' => $session,
        ]);
    }

    public function submitAnswer(Request $request, GameSession $session, Riddle $riddle)
    {
        $request->validate(['answer' => 'required|string']);

        if (Str::lower($request->answer) === Str::lower($riddle->reponse)) {
            // Success logic
            $session->scores()->updateOrCreate(
                ['user_id' => $request->user()->id],
                ['points' => \DB::raw('points + 100')]
            );

            return back()->with('success', 'Bonne réponse !');
        }

        return back()->with('error', 'Mauvaise réponse, réessayez.');
    }
}
