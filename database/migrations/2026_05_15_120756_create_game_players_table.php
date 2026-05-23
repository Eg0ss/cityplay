<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('game_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('statut')->default('en_attente'); // en_attente, pret, en_jeu
            // $table->string('mode_choisi')->nullable(); // gaming, decouverte
            $table->string('global_mode')->nullable(); // decouverte, gaming, mixte
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_players');
    }
};