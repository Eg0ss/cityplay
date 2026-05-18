<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_player_riddle_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_riddle_id')->constrained('game_riddles')->onDelete('cascade');
            $table->string('mode_choisi')->nullable(); // decouverte, gaming
            $table->string('transport_mode')->nullable(); // walking, driving, bicycling
            $table->integer('time_limit')->nullable(); // en secondes
            $table->timestamp('started_at')->nullable();
            $table->integer('total_paused_time')->default(0); // en secondes
            $table->timestamp('last_paused_at')->nullable();
            $table->string('status')->default('en_attente'); // en_attente, en_cours, en_pause, gagne, perdu
            $table->integer('points_earned')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_player_riddle_attempts');
    }
};
