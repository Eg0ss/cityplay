<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    protected $fillable = ['nom', 'ville', 'departement', 'lat', 'lng', 'rayon_marge', 'is_active'];

    public function riddles()
    {
        return $this->hasMany(Riddle::class);
    }
}