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
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->string('mode')->nullable()->change();
        });
        Schema::table('game_players', function (Blueprint $table) {
            $table->string('mode_choisi')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->string('mode')->nullable(false)->change();
        });
        Schema::table('game_players', function (Blueprint $table) {
            $table->string('mode_choisi')->nullable(false)->change();
        });
    }
};
