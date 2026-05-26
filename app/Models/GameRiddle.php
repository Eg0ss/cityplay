<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRiddle extends Model
{
    protected $fillable = [
        'session_id',
        'riddle_id',
        'repondu_par',
        'verrouille_a',
        'statut',
        'locked_by_player_id',
    ];

    protected $casts = [
        'verrouille_a' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }

    public function riddle()
    {
        return $this->belongsTo(Riddle::class);
    }

    public function solver()
    {
        return $this->belongsTo(User::class, 'repondu_par');
    }

    public function attempts()
    {
        return $this->hasMany(GamePlayerRiddleAttempt::class, 'game_riddle_id');
    }

    public function lockedByPlayer()
    {
        return $this->belongsTo(GamePlayer::class, 'locked_by_player_id');
    }
}