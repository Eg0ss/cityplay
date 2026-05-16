<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $fillable = [
        'nom', 
        'image',
        'ville', 
        'city_id', 
        'departement', 
        'lat', 
        'lng', 
        'rayon_marge', 
        'is_active',
        'verified_description'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function riddles()
    {
        return $this->hasMany(Riddle::class);
    }
}