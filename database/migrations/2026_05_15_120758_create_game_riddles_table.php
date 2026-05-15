<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_riddles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('game_sessions')->onDelete('cascade');
            $table->foreignId('riddle_id')->constrained('riddles')->onDelete('cascade');
            $table->foreignId('repondu_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verrouille_a')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_riddles');
    }
};
