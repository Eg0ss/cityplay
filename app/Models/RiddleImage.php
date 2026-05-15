<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiddleImage extends Model
{
    protected $fillable = ['riddle_id', 'image_path'];

    public function riddle()
    {
        return $this->belongsTo(Riddle::class);
    }
}
