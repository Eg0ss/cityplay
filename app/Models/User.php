<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_admin',
        'is_partner',
    ];

    public function gameSessions()
    {
        return $this->belongsToMany(GameSession::class, 'game_players', 'user_id', 'session_id');
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function gamePlayers()
    {
        return $this->hasMany(GamePlayer::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_partner' => 'boolean',
        ];
    }
}
