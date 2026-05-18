<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'statut', 'lien_token', 'max_joueurs', 
        'level', 'location_type', 'location_id', 
        'riddles_count', 'type', 'challenger_mode'
    ];

    public function players()
    {
        return $this->hasMany(GamePlayer::class, 'session_id');
    }

    public function attempts()
    {
        return $this->hasMany(GamePlayerRiddleAttempt::class, 'game_session_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'game_players', 'session_id', 'user_id');
    }

    public function gameRiddles()
    {
        return $this->hasMany(GameRiddle::class, 'session_id');
    }

    public function riddles()
    {
        return $this->belongsToMany(Riddle::class, 'game_riddles', 'session_id', 'riddle_id');
    }

    public function scores()
    {
        return $this->hasMany(Score::class, 'session_id');
    }
}
