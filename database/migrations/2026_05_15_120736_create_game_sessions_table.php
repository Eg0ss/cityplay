<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('lien_token')->unique();
            $table->string('statut')->default('en_attente');
            $table->string('mode')->nullable(); // gaming, decouverte, voyage (legacy)
            $table->string('level')->nullable(); // facile, intermediaire, difficile
            $table->string('location_type')->nullable(); // departement, commune, city, place
            $table->unsignedBigInteger('location_id')->nullable();
            $table->integer('riddles_count')->default(1);
            $table->string('type')->default('solo'); // solo, participants, challengers
            $table->string('challenger_mode')->nullable(); // reponse_par_membre, reponse_par_tous
            $table->integer('max_joueurs')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};

