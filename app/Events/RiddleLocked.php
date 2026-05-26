<?php

namespace App\Events;

use App\Models\GameRiddle;
use App\Models\GamePlayer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiddleLocked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameRiddle $gameRiddle,
        public GamePlayer $lockedByPlayer,
        public string $sessionToken
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('game.' . $this->sessionToken);
    }

    public function broadcastAs(): string
    {
        return 'riddle.locked';
    }

    public function broadcastWith(): array
    {
        return [
            'game_riddle_id'      => $this->gameRiddle->id,
            'riddle_id'           => $this->gameRiddle->riddle_id,
            'locked_by_player_id' => $this->lockedByPlayer->id,
            'locked_by_user_id'   => $this->lockedByPlayer->user_id,
            'locked_by_name'      => $this->lockedByPlayer->user->name ?? 'Un joueur',
            'locked_at'           => now()->toISOString(),
        ];
    }
}