<?php

namespace App\Events;

use App\Models\GameSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;

    public function __construct(GameSession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn()
    {
        return new Channel('game.' . $this->session->lien_token);
    }

    public function broadcastWith()
    {
        $sessionWithData = $this->session->load([
            'attempts' => function ($query) {
                $query->select('id', 'game_session_id', 'user_id', 'game_riddle_id', 'status', 'points_earned');
            },
            'attempts.user:id,name',
            'players.user:id,name',
            'gameRiddles:id,session_id,riddle_id,statut,locked_by_player_id,verrouille_a',
            'gameRiddles.lockedByPlayer:id,user_id',
            'gameRiddles.lockedByPlayer.user:id,name',
        ]);

        return [
            'updated' => true,
            'session' => [
                'id'          => $sessionWithData->id,
                'statut'      => $sessionWithData->statut,
                'type'        => $sessionWithData->type,
                'attempts'    => $sessionWithData->attempts,
                'players'     => $sessionWithData->players,
                'gameRiddles' => $sessionWithData->gameRiddles->map(fn($gr) => [
                    'id'                  => $gr->id,
                    'riddle_id'           => $gr->riddle_id,
                    'statut'              => $gr->statut,
                    'locked_by_player_id' => $gr->locked_by_player_id,
                    'locked_by_name'      => $gr->lockedByPlayer?->user?->name,
                    'verrouille_a'        => $gr->verrouille_a,
                ]),
            ],
        ];
    }
}