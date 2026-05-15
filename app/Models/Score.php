<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $fillable = ['session_id', 'user_id', 'points', 'temps_resolution'];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
