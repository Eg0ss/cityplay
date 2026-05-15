<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
    protected $fillable = ['session_id', 'user_id', 'mode_choisi', 'statut'];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
