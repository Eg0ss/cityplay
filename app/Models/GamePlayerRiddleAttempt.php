<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlayerRiddleAttempt extends Model
{
    protected $fillable = [
        'game_session_id', 'user_id', 'game_riddle_id', 
        'mode_choisi', 'transport_mode', 'time_limit', 
        'started_at', 'total_paused_time', 'last_paused_at', 
        'status', 'points_earned'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_paused_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gameRiddle()
    {
        return $this->belongsTo(GameRiddle::class, 'game_riddle_id');
    }
}
