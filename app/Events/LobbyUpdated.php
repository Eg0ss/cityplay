<?php

namespace App\Events;

use App\Models\GameSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;

    public function __construct(GameSession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn()
    {
        return new Channel('lobby.' . $this->session->lien_token);
    }

    public function broadcastWith()
    {
        return [
            'updated' => true,
        ];
    }
}
