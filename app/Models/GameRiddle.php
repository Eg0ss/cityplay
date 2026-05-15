<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRiddle extends Model
{
    protected $fillable = ['session_id', 'riddle_id', 'repondu_par', 'verrouille_a'];

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
}
