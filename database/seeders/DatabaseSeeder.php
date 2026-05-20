<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Création de l'Admin
        User::create([
            'name' => 'Administrateur',
            'email' => 'admin@cityplay.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Création des 5 Joueurs
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "Joueur $i",
                'email' => "joueur$i@cityplay.com",
                'password' => Hash::make('password'),
                'role' => 'player',
            ]);
        }

        // $this->call([
        //     GameDataSeeder::class,
        // ]);
    }
}
