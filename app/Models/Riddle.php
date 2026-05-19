<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Riddle extends Model
{
    protected $fillable = ['place_id', 'niveau', 'description', 'reponse', 'mcq_options', 'indice_id'];

    protected $casts = [
        'mcq_options' => 'array',
    ];

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function images()
    {
        return $this->hasMany(RiddleImage::class);
    }

    public function gameRiddles()
    {
        return $this->hasMany(GameRiddle::class);
    }

    public function hints()
    {
        return $this->hasMany(Hint::class)->orderBy('order');
    }
}
