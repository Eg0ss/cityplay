<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    protected $fillable = ['name', 'city', 'description', 'latitude', 'longitude', 'is_active'];

    public function enigmas(): HasMany
    {
        return $this->hasMany(Enigma::class);
    }
}