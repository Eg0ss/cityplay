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
            $table->string('level')->nullable(); // facile, intermediaire, difficile
            $table->string('location_type')->nullable(); // departement, commune, city, place
            $table->unsignedBigInteger('location_id')->nullable();
            $table->integer('riddles_count')->default(1);
            $table->string('type')->default('solo'); // solo, participants, challengers
            $table->string('challenger_mode')->nullable(); // reponse_par_membre, reponse_par_tous
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['level', 'location_type', 'location_id', 'riddles_count', 'type', 'challenger_mode']);
        });
    }
};
