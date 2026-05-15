<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Riddle extends Model
{
    protected $fillable = ['place_id', 'niveau', 'description', 'reponse', 'photos'];

    protected $casts = [
        'photos' => 'json',
    ];

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function gameRiddles()
    {
        return $this->hasMany(GameRiddle::class);
    }
}
