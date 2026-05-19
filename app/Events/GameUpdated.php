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
        // Enrichir l'événement avec les données détaillées
        $sessionWithData = $this->session->load([
            'attempts' => function ($query) {
                $query->select('id', 'game_session_id', 'user_id', 'game_riddle_id', 'status', 'points_earned');
            },
            'attempts.user:id,name',
            'players.user:id,name',
        ]);

        return [
            'updated' => true,
            'session' => [
                'id' => $sessionWithData->id,
                'statut' => $sessionWithData->statut,
                'type' => $sessionWithData->type,
                'attempts' => $sessionWithData->attempts,
                'players' => $sessionWithData->players,
            ],
        ];
    }
}
