<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hint extends Model
{
    protected $fillable = [
        'riddle_id',
        'type',
        'content',
        'difficulty_level',
        'order',
    ];

    public function riddle()
    {
        return $this->belongsTo(Riddle::class);
    }
}
